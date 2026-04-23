<?php

namespace App\Http\Controllers;

use App\Models\CourseReview;
use App\Models\DictionaryEntry;
use App\Models\ForumPost;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }

    private function resolveModerationAction(ModerationAction $moderationAction, string $resolutionNote): void
    {
        $moderationAction->status = 'resolved';
        $moderationAction->moderator_user_id = (int) (Auth::id() ?? 0);
        $moderationAction->resolution_note = $resolutionNote;
        $moderationAction->save();
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        DictionaryEntry::seedDefaultsIfEmpty();

        $allowedPageSizes = [10, 25, 50, 100];

        $userSearch = trim((string) $request->query('user_search', ''));
        $userRole = (string) $request->query('user_role', '');
        $userPerPage = (int) $request->query('user_per_page', 25);
        if (!in_array($userPerPage, $allowedPageSizes, true)) {
            $userPerPage = 25;
        }

        $usersQuery = User::query();
        if ($userSearch !== '') {
            $usersQuery->where(function ($query) use ($userSearch): void {
                $query
                    ->where('name', 'like', '%' . $userSearch . '%')
                    ->orWhere('email', 'like', '%' . $userSearch . '%');
            });
        }
        if (in_array($userRole, ['admin', 'teacher', 'student'], true)) {
            $usersQuery->where('role', $userRole);
        }

        $wordSearch = trim((string) $request->query('word_search', ''));
        $wordPerPage = (int) $request->query('word_per_page', 25);
        if (!in_array($wordPerPage, $allowedPageSizes, true)) {
            $wordPerPage = 25;
        }

        $dictionaryQuery = DictionaryEntry::query();
        if ($wordSearch !== '') {
            $dictionaryQuery->where(function ($query) use ($wordSearch): void {
                $query
                    ->where('word', 'like', '%' . $wordSearch . '%')
                    ->orWhere('translation', 'like', '%' . $wordSearch . '%')
                    ->orWhere('transcription', 'like', '%' . $wordSearch . '%');
            });
        }

        $reportStatus = (string) $request->query('report_status', 'pending');
        if (!in_array($reportStatus, ['pending', 'resolved', 'all'], true)) {
            $reportStatus = 'pending';
        }

        $moderationReports = ModerationAction::query()
            ->where('entity_type', 'forum_post')
            ->when($reportStatus !== 'all', fn ($query) => $query->where('status', $reportStatus))
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'reports_page')
            ->withQueryString();

        $forumPostIds = $moderationReports->getCollection()
            ->pluck('entity_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $forumPostsById = ForumPost::query()
            ->whereIn('id', $forumPostIds)
            ->with(['author:id,name', 'thread:id,lesson_id,title'])
            ->get()
            ->keyBy('id');

        $reviewStatus = (string) $request->query('review_status', 'pending');
        if (!in_array($reviewStatus, ['pending', 'approved', 'all'], true)) {
            $reviewStatus = 'pending';
        }

        $courseReviews = CourseReview::query()
            ->with(['author:id,name,email', 'course:id,title'])
            ->when($reviewStatus !== 'all', fn ($query) => $query->where('is_approved', $reviewStatus === 'approved'))
            ->orderByDesc('updated_at')
            ->paginate(25, ['*'], 'reviews_page')
            ->withQueryString();

        return view('admin.index', [
            'users' => $usersQuery
                ->orderBy('name')
                ->paginate($userPerPage, ['*'], 'users_page')
                ->withQueryString(),
            'dictionaryEntries' => $dictionaryQuery
                ->orderBy('word')
                ->paginate($wordPerPage, ['*'], 'words_page')
                ->withQueryString(),
            'totalUsers' => User::query()->count(),
            'totalDictionaryEntries' => DictionaryEntry::query()->count(),
            'userFilters' => [
                'search' => $userSearch,
                'role' => $userRole,
                'per_page' => $userPerPage,
            ],
            'wordFilters' => [
                'search' => $wordSearch,
                'per_page' => $wordPerPage,
            ],
            'moderationReports' => $moderationReports,
            'forumPostsById' => $forumPostsById,
            'reportFilters' => [
                'status' => $reportStatus,
            ],
            'courseReviews' => $courseReviews,
            'reviewFilters' => [
                'status' => $reviewStatus,
            ],
        ]);
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'role' => 'required|in:admin,teacher,student',
        ]);

        if (Auth::id() === $user->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Администратор не может снять роль у самого себя.']);
        }

        $user->update(['role' => $data['role']]);

        return back()->with('admin_status', 'Роль пользователя обновлена.');
    }

    public function storeKnowledge(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'word' => 'required|string|max:255',
            'translation' => 'required|string|max:255',
            'transcription' => 'nullable|string|max:255',
            'complexity_index' => 'nullable|numeric|min:0|max:9.99',
        ]);

        DictionaryEntry::query()->create([
            'word' => $data['word'],
            'translation' => $data['translation'],
            'transcription' => $data['transcription'] ?? null,
            'complexity_index' => $data['complexity_index'] ?? 0,
        ]);

        return back()->with('admin_status', 'Запись в базе знаний добавлена.');
    }

    public function importKnowledge(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $data['csv_file']->getRealPath();
        if ($path === false) {
            return back()->withErrors(['csv_file' => 'Не удалось прочитать CSV-файл.']);
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Не удалось открыть CSV-файл.']);
        }

        $imported = 0;
        $line = 0;
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $line++;
            if (count($row) === 0) {
                continue;
            }

            $word = trim((string) ($row[0] ?? ''));
            $translation = trim((string) ($row[1] ?? ''));
            $transcription = trim((string) ($row[2] ?? ''));
            $complexity = (string) ($row[3] ?? '');

            if ($line === 1 && mb_strtolower($word) === 'word' && mb_strtolower($translation) === 'translation') {
                continue;
            }
            if ($word === '' || $translation === '') {
                continue;
            }

            $complexityValue = is_numeric($complexity) ? (float) $complexity : 0.0;
            $complexityValue = max(0, min(9.99, $complexityValue));

            DictionaryEntry::query()->updateOrCreate(
                [
                    'word' => $word,
                    'translation' => $translation,
                ],
                [
                    'transcription' => $transcription !== '' ? $transcription : null,
                    'complexity_index' => $complexityValue,
                ],
            );
            $imported++;
        }

        fclose($handle);

        return back()->with('admin_status', "Импорт завершен. Обработано строк: {$imported}.");
    }

    public function exportKnowledge(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $filename = 'dictionary_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['word', 'translation', 'transcription', 'complexity_index']);
            DictionaryEntry::query()
                ->orderBy('word')
                ->chunk(500, function ($chunk) use ($output): void {
                    foreach ($chunk as $entry) {
                        fputcsv($output, [
                            (string) $entry->word,
                            (string) $entry->translation,
                            (string) ($entry->transcription ?? ''),
                            (string) number_format((float) $entry->complexity_index, 2, '.', ''),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroyKnowledge(DictionaryEntry $dictionaryEntry): RedirectResponse
    {
        $this->authorizeAdmin();

        $dictionaryEntry->delete();

        return back()->with('admin_status', 'Запись из базы знаний удалена.');
    }

    public function approveReview(CourseReview $courseReview): RedirectResponse
    {
        $this->authorizeAdmin();

        $courseReview->is_approved = true;
        $courseReview->save();

        return back()->with('admin_status', 'Отзыв одобрен.');
    }

    public function rejectReview(CourseReview $courseReview): RedirectResponse
    {
        $this->authorizeAdmin();

        $courseReview->is_approved = false;
        $courseReview->save();

        return back()->with('admin_status', 'Отзыв скрыт из публичного рейтинга.');
    }

    public function hideReportedPost(ModerationAction $moderationAction): RedirectResponse
    {
        $this->authorizeAdmin();

        abort_unless($moderationAction->entity_type === 'forum_post', 404);

        $post = ForumPost::query()->find((int) $moderationAction->entity_id);
        if ($post !== null) {
            $post->is_hidden = true;
            $post->save();
        }

        $this->resolveModerationAction($moderationAction, 'Сообщение скрыто администратором.');

        return back()->with('admin_status', 'Сообщение скрыто, жалоба закрыта.');
    }

    public function restoreReportedPost(ModerationAction $moderationAction): RedirectResponse
    {
        $this->authorizeAdmin();

        abort_unless($moderationAction->entity_type === 'forum_post', 404);

        $post = ForumPost::query()->find((int) $moderationAction->entity_id);
        if ($post !== null) {
            $post->is_hidden = false;
            $post->save();
        }

        $this->resolveModerationAction($moderationAction, 'Сообщение возвращено после проверки.');

        return back()->with('admin_status', 'Сообщение возвращено, жалоба обработана.');
    }
}

