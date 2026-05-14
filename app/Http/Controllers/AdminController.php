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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private const PAGE_SIZES = [10, 25, 50, 100];

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

    private function dictionaryLanguageId(): int
    {
        return (int) (DB::table('languages')
            ->where('code', 'bxr')
            ->value('id') ?? 0);
    }

    private function dashboardCounters(): array
    {
        $dictionaryLanguageId = $this->dictionaryLanguageId();

        return [
            'totalUsers' => User::query()->count(),
            'totalDictionaryEntries' => DictionaryEntry::query()
                ->when($dictionaryLanguageId > 0, fn ($query) => $query->where('language_id', $dictionaryLanguageId))
                ->where('status', 'published')
                ->count(),
            'totalPendingReviews' => CourseReview::query()->where('is_approved', false)->count(),
            'totalPendingReports' => ModerationAction::query()
                ->where('entity_type', 'forum_post')
                ->where('status', 'pending')
                ->count(),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        DictionaryEntry::seedDefaultsIfEmpty();

        return view('admin.hub', [
            ...$this->dashboardCounters(),
            'adminUser' => $request->user(),
        ]);
    }

    public function usersPage(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();

        $userSearch = trim((string) $request->query('user_search', ''));
        $userRole = (string) $request->query('user_role', '');
        $userPerPage = (int) $request->query('user_per_page', 25);
        if (!in_array($userPerPage, self::PAGE_SIZES, true)) {
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

        $data = [
            ...$this->dashboardCounters(),
            'users' => $usersQuery
                ->orderBy('name')
                ->paginate($userPerPage, ['*'], 'users_page')
                ->withQueryString(),
            'userFilters' => [
                'search' => $userSearch,
                'role' => $userRole,
                'per_page' => $userPerPage,
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'table_html' => view('admin.partials.users-table', [
                    'users' => $data['users'],
                ])->render(),
            ]);
        }

        return view('admin.users', $data);
    }

    public function knowledgePage(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();
        DictionaryEntry::seedDefaultsIfEmpty();
        $dictionaryLanguageId = $this->dictionaryLanguageId();

        $wordSearch = trim((string) $request->query('word_search', ''));
        $wordPerPage = (int) $request->query('word_per_page', 25);
        if (!in_array($wordPerPage, self::PAGE_SIZES, true)) {
            $wordPerPage = 25;
        }

        $dictionaryQuery = DictionaryEntry::query()
            ->when($dictionaryLanguageId > 0, fn ($query) => $query->where('language_id', $dictionaryLanguageId))
            ->where('status', 'published');
        if ($wordSearch !== '') {
            $dictionaryQuery->where(function ($query) use ($wordSearch): void {
                $query
                    ->where('word', 'like', '%' . $wordSearch . '%')
                    ->orWhere('translation', 'like', '%' . $wordSearch . '%')
                    ->orWhere('transcription', 'like', '%' . $wordSearch . '%');
            });
        }

        $data = [
            ...$this->dashboardCounters(),
            'dictionaryEntries' => $dictionaryQuery
                ->orderBy('word')
                ->paginate($wordPerPage, ['*'], 'words_page')
                ->withQueryString(),
            'wordFilters' => [
                'search' => $wordSearch,
                'per_page' => $wordPerPage,
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'table_html' => view('admin.partials.knowledge-table', [
                    'dictionaryEntries' => $data['dictionaryEntries'],
                ])->render(),
            ]);
        }

        return view('admin.knowledge', $data);
    }

    public function moderationPage(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();

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

        $data = [
            ...$this->dashboardCounters(),
            'moderationReports' => $moderationReports,
            'forumPostsById' => $forumPostsById,
            'reportFilters' => [
                'status' => $reportStatus,
            ],
            'courseReviews' => $courseReviews,
            'reviewFilters' => [
                'status' => $reviewStatus,
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'reviews_html' => view('admin.partials.moderation-reviews-table', [
                    'courseReviews' => $data['courseReviews'],
                ])->render(),
                'reports_html' => view('admin.partials.moderation-reports-table', [
                    'moderationReports' => $data['moderationReports'],
                ])->render(),
            ]);
        }

        return view('admin.moderation', $data);
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'role' => 'required|in:admin,teacher,student',
        ]);

        if (Auth::id() === $user->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Администратор не может снять роль у самого себя.']);
        }

        $user->update(['role' => $data['role']]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Роль пользователя обновлена.']);
        }
        return back()->with('admin_status', 'Роль пользователя обновлена.');
    }

    public function storeKnowledge(Request $request): RedirectResponse|JsonResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Запись в базе знаний добавлена.',
            ]);
        }

        return back()->with('admin_status', 'Запись в базе знаний добавлена.');
    }

    public function importKnowledge(Request $request): RedirectResponse|JsonResponse
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
        $languageId = $this->dictionaryLanguageId();
        if ($languageId < 1) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Не удалось определить язык словаря (bxr).']);
        }
        $imported = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $line = 0;
        $columnMap = null;
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $line++;
            if (count($row) === 0) {
                $skipped++;
                continue;
            }
            $normalized = array_map(
                static fn ($cell) => trim((string) $cell),
                $row
            );
            if ($line === 1) {
                if (isset($normalized[0])) {
                    $normalized[0] = ltrim($normalized[0], "\xEF\xBB\xBF");
                }
                $columnMap = $this->resolveCsvColumnMap($normalized);
                if ($columnMap !== null) {
                    continue;
                }
            }
            [$word, $translation, $transcription, $complexity] = $this->extractKnowledgeRow($normalized, $columnMap);
            if ($word === '' || $translation === '') {
                $skipped++;
                continue;
            }
            $complexityValue = is_numeric($complexity) ? (float) $complexity : 0.0;
            $complexityValue = max(0, min(9.99, $complexityValue));
            $entry = DictionaryEntry::query()->updateOrCreate(
                [
                    'language_id' => $languageId,
                    'word' => $word,
                    'translation' => $translation,
                ],
                [
                    'transcription' => $transcription !== '' ? $transcription : null,
                    'complexity_index' => $complexityValue,
                    'status' => 'published',
                ],
            );
            if ($entry->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
            $imported++;
        }
        fclose($handle);
        $message = "Импорт завершен. Обработано: {$imported}; создано: {$created}; обновлено: {$updated}; пропущено: {$skipped}.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('admin_status', $message);
    }

    private function resolveCsvColumnMap(array $headerRow): ?array
    {
        $normalized = array_map(
            static fn (string $value): string => mb_strtolower(trim($value)),
            $headerRow
        );
        $indexByName = [];
        foreach ($normalized as $idx => $name) {
            if ($name !== '' && !array_key_exists($name, $indexByName)) {
                $indexByName[$name] = (int) $idx;
            }
        }
        $wordIdx = $indexByName['word']
            ?? $indexByName['слово']
            ?? $indexByName['buryat']
            ?? $indexByName['бурятский']
            ?? null;
        $translationIdx = $indexByName['translation']
            ?? $indexByName['перевод']
            ?? $indexByName['russian']
            ?? $indexByName['русский']
            ?? null;
        $transcriptionIdx = $indexByName['transcription']
            ?? $indexByName['транскрипция']
            ?? null;
        $complexityIdx = $indexByName['complexity_index']
            ?? $indexByName['complexity']
            ?? $indexByName['сложность']
            ?? null;
        if ($wordIdx === null || $translationIdx === null) {
            return null;
        }
        return [
            'word' => (int) $wordIdx,
            'translation' => (int) $translationIdx,
            'transcription' => $transcriptionIdx !== null ? (int) $transcriptionIdx : null,
            'complexity' => $complexityIdx !== null ? (int) $complexityIdx : null,
        ];
    }

    private function extractKnowledgeRow(array $row, ?array $columnMap): array
    {
        if ($columnMap !== null) {
            $word = trim((string) ($row[$columnMap['word']] ?? ''));
            $translation = trim((string) ($row[$columnMap['translation']] ?? ''));
            $transcription = trim((string) ($row[$columnMap['transcription']] ?? ''));
            $complexity = (string) ($row[$columnMap['complexity']] ?? '');
            return [$word, $translation, $transcription, $complexity];
        }
        $hasLegacyId = isset($row[0]) && preg_match('/^\d+$/', (string) $row[0]) === 1;
        if ($hasLegacyId) {
            $word = trim((string) ($row[1] ?? ''));
            $translation = trim((string) ($row[2] ?? ''));
            $transcription = trim((string) ($row[3] ?? ''));
            $complexity = (string) ($row[4] ?? '');
            return [$word, $translation, $transcription, $complexity];
        }
        $word = trim((string) ($row[0] ?? ''));
        $translation = trim((string) ($row[1] ?? ''));
        $transcription = trim((string) ($row[2] ?? ''));
        $complexity = (string) ($row[3] ?? '');
        return [$word, $translation, $transcription, $complexity];
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

    public function destroyKnowledge(Request $request, DictionaryEntry $dictionaryEntry): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        $dictionaryEntry->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Запись из базы знаний удалена.',
            ]);
        }

        return back()->with('admin_status', 'Запись из базы знаний удалена.');
    }

    public function approveReview(Request $request, CourseReview $courseReview): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        $courseReview->is_approved = true;
        $courseReview->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Отзыв одобрен.']);
        }
        return back()->with('admin_status', 'Отзыв одобрен.');
    }

    public function rejectReview(Request $request, CourseReview $courseReview): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        $courseReview->is_approved = false;
        $courseReview->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Отзыв скрыт из публичного рейтинга.']);
        }
        return back()->with('admin_status', 'Отзыв скрыт из публичного рейтинга.');
    }

    public function hideReportedPost(Request $request, ModerationAction $moderationAction): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        abort_unless($moderationAction->entity_type === 'forum_post', 404);

        $post = ForumPost::query()->find((int) $moderationAction->entity_id);
        if ($post !== null) {
            $post->is_hidden = true;
            $post->save();
        }

        $this->resolveModerationAction($moderationAction, 'Сообщение скрыто администратором.');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Сообщение скрыто, жалоба закрыта.']);
        }
        return back()->with('admin_status', 'Сообщение скрыто, жалоба закрыта.');
    }

    public function restoreReportedPost(Request $request, ModerationAction $moderationAction): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        abort_unless($moderationAction->entity_type === 'forum_post', 404);

        $post = ForumPost::query()->find((int) $moderationAction->entity_id);
        if ($post !== null) {
            $post->is_hidden = false;
            $post->save();
        }

        $this->resolveModerationAction($moderationAction, 'Сообщение возвращено после проверки.');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Сообщение возвращено, жалоба обработана.']);
        }
        return back()->with('admin_status', 'Сообщение возвращено, жалоба обработана.');
    }
}
