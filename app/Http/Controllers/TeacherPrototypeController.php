<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Achievement;
use App\Services\TeacherCourseAccessService;
use App\Services\TeacherDashboardViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherPrototypeController extends Controller
{
    public function __construct(
        private readonly TeacherCourseAccessService $courseAccess,
        private readonly TeacherDashboardViewService $dashboardViewService,
    ) {
    }

    private function normalizeCatalogSort(string $sort): string
    {
        $allowed = ['rating_desc', 'rating_asc', 'title_asc', 'title_desc'];
        return in_array($sort, $allowed, true) ? $sort : 'rating_desc';
    }

    private function normalizeCatalogMinRating(string $value): int
    {
        $rating = (int) $value;
        if ($rating < 0) {
            return 0;
        }
        if ($rating > 5) {
            return 5;
        }
        return $rating;
    }

    private function normalizeStudentReturnPage(string $page): string
    {
        return in_array($page, ['student_dashboard', 'student_profile', 'student_courses', 'student_progress', 'student_catalog'], true)
            ? $page
            : 'student_profile';
    }

    public function index(Request $request)
    {
        return view('teacher.indes', $this->dashboardViewService->build($request));
    }

    private function renderNamedPage(Request $request, string $page)
    {
        $request->query->set('page', $page);
        return view('teacher.indes', $this->dashboardViewService->build($request));
    }

    public function studentDashboard(Request $request)
    {
        return $this->renderNamedPage($request, 'student_profile');
    }

    public function studentProfilePage(Request $request)
    {
        return $this->renderNamedPage($request, 'student_profile');
    }

    public function studentCoursesPage(Request $request)
    {
        return $this->renderNamedPage($request, 'student_courses');
    }

    public function studentProgressPage(Request $request)
    {
        return $this->renderNamedPage($request, 'student_progress');
    }

    public function studentCatalog(Request $request)
    {
        return $this->renderNamedPage($request, 'student_catalog');
    }

    public function updateStudentProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore((int) $user->id)],
        ]);

        $user->update([
            'name' => trim((string) $data['name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
        ]);

        return redirect()
            ->route('student.profile')
            ->with('student_status', 'Профиль обновлен.');
    }

    public function teacherCoursesPage(Request $request)
    {
        return $this->renderNamedPage($request, 'teacher_courses');
    }

    public function teacherHomePage(Request $request)
    {
        return $this->renderNamedPage($request, 'teacher_home');
    }

    public function teacherAnalyticsPage(Request $request)
    {
        return $this->renderNamedPage($request, 'teacher_analytics');
    }

    public function teacherPanelPage(Request $request)
    {
        return $this->renderNamedPage($request, 'teacher_panel');
    }

    public function teacherAchievementsPage(Request $request)
    {
        return $this->renderNamedPage($request, 'teacher_achievements');
    }

    public function lessonView(Request $request, int $id)
    {
        if (($request->user()->role ?? '') === 'student') {
            $isAvailableForStudent = DB::table('lessons as l')
                ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
                ->join('courses as c', 'c.id', '=', 'cm.course_id')
                ->join('user_course_progress as ucp', function ($join) use ($request) {
                    $join->on('ucp.course_id', '=', 'c.id')
                        ->where('ucp.user_id', '=', (int) $request->user()->id)
                        ->whereNotNull('ucp.started_at');
                })
                ->where('l.id', $id)
                ->where('l.status', 'published')
                ->where('c.status', '!=', 'archived')
                ->exists();

            if (!$isAvailableForStudent) {
                return redirect()
                    ->route('student.catalog')
                    ->with('student_status', 'Урок недоступен. Сначала добавьте курс из каталога.');
            }
        }

        $request->query->set('page', 'lesson_view');
        $request->query->set('id', (string) $id);
        return view('teacher.indes', $this->dashboardViewService->build($request));
    }

    public function enrollCourse(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::query()
            ->where('id', $courseId)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->firstOrFail();
        $userId = (int) $request->user()->id;

        $exists = DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->where('course_id', (int) $course->id)
            ->exists();

        if (!$exists) {
            DB::table('user_course_progress')->insert([
                'user_id' => $userId,
                'course_id' => (int) $course->id,
                'progress_percent' => 0,
                'completed_lessons' => 0,
                'xp_earned' => 0,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('user_course_progress')
                ->where('user_id', $userId)
                ->where('course_id', (int) $course->id)
                ->update(['updated_at' => now()]);
        }

        $returnPage = $this->normalizeStudentReturnPage((string) $request->input('return_page', 'student_dashboard'));
        $redirectParams = ['page' => $returnPage];
        if ($returnPage === 'student_catalog') {
            $returnQ = trim((string) $request->input('return_q', ''));
            $returnSort = $this->normalizeCatalogSort((string) $request->input('return_sort', 'rating_desc'));
            $returnLevel = trim((string) $request->input('return_level', ''));
            $returnMinRating = $this->normalizeCatalogMinRating((string) $request->input('return_min_rating', '0'));
            if ($returnQ !== '') {
                $redirectParams['q'] = $returnQ;
            }
            $redirectParams['sort'] = $returnSort;
            if ($returnLevel !== '') {
                $redirectParams['level'] = $returnLevel;
            }
            if ($returnMinRating > 0) {
                $redirectParams['min_rating'] = $returnMinRating;
            }
        }

        return redirect()->route('teacher.indes', $redirectParams)
            ->with('student_status', 'Операция выполнена.');
    }

    public function leaveCourse(Request $request, int $courseId): RedirectResponse
    {
        DB::table('user_course_progress')
            ->where('user_id', (int) $request->user()->id)
            ->where('course_id', $courseId)
            ->delete();

        $returnPage = $this->normalizeStudentReturnPage((string) $request->input('return_page', 'student_dashboard'));
        $redirectParams = ['page' => $returnPage];
        if ($returnPage === 'student_catalog') {
            $returnQ = trim((string) $request->input('return_q', ''));
            $returnSort = $this->normalizeCatalogSort((string) $request->input('return_sort', 'rating_desc'));
            $returnLevel = trim((string) $request->input('return_level', ''));
            $returnMinRating = $this->normalizeCatalogMinRating((string) $request->input('return_min_rating', '0'));
            if ($returnQ !== '') {
                $redirectParams['q'] = $returnQ;
            }
            $redirectParams['sort'] = $returnSort;
            if ($returnLevel !== '') {
                $redirectParams['level'] = $returnLevel;
            }
            if ($returnMinRating > 0) {
                $redirectParams['min_rating'] = $returnMinRating;
            }
        }

        return redirect()->route('teacher.indes', $redirectParams)
            ->with('student_status', 'Операция выполнена.');
    }

    public function storeCourseReview(Request $request, int $courseId): RedirectResponse
    {
        $user = $request->user();
        if (($user->role ?? '') !== 'student') {
            abort(403);
        }

        $isEnrolled = DB::table('user_course_progress')
            ->where('user_id', (int) $user->id)
            ->where('course_id', $courseId)
            ->exists();

        abort_unless($isEnrolled, 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:2000',
        ]);

        CourseReview::query()->updateOrCreate(
            [
                'course_id' => $courseId,
                'user_id' => (int) $user->id,
            ],
            [
                'rating' => (int) $data['rating'],
                'review_text' => trim((string) ($data['review_text'] ?? '')),
                'is_approved' => true,
            ],
        );

        return redirect()
            ->route('teacher.indes', ['page' => 'student_dashboard'])
            ->with('student_status', 'Оценка и отзыв по курсу сохранены.');
    }

    public function toggleCoursePublication(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        if (($request->user()->role ?? '') !== 'teacher') {
            abort(403);
        }

        $ownedCourse = $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $course->id);
        $nextStatus = (string) $ownedCourse->status === 'published' ? 'draft' : 'published';

        $ownedCourse->update([
            'status' => $nextStatus,
        ]);

        $message = $nextStatus === 'published'
            ? 'Курс опубликован и доступен ученикам в каталоге.'
            : 'Курс снят с публикации и скрыт из каталога учеников.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'course' => [
                    'id' => (int) $ownedCourse->id,
                    'status' => $nextStatus,
                ],
            ]);
        }

        return redirect()
            ->route('teacher.courses.page')
            ->with('student_status', $message);
    }

    public function deleteCourse(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        if (($request->user()->role ?? '') !== 'teacher') {
            abort(403);
        }

        $ownedCourse = $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $course->id);
        $confirmCourseTitle = trim((string) $request->input('confirm_course_title', ''));
        $courseTitle = trim((string) ($ownedCourse->title ?? ''));

        if ($confirmCourseTitle === '' || mb_strtolower($confirmCourseTitle) !== mb_strtolower($courseTitle)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Название курса не совпало. Удаление отменено.',
                ], 422);
            }

            return redirect()
                ->route('teacher.courses.page')
                ->with('student_status', 'Название курса не совпало. Удаление отменено.');
        }

        $deletedCourseId = (int) $ownedCourse->id;
        DB::transaction(function () use ($ownedCourse): void {
            Achievement::query()
                ->where('is_system', false)
                ->where('course_id', (int) $ownedCourse->id)
                ->delete();

            $ownedCourse->delete();
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Курс удален.',
                'course' => [
                    'id' => $deletedCourseId,
                ],
            ]);
        }

        return redirect()
            ->route('teacher.courses.page')
            ->with('student_status', 'Курс удален.');
    }

    public function addCourseAchievement(Request $request): JsonResponse
    {
        if (($request->user()->role ?? '') !== 'teacher') {
            abort(403);
        }

        $data = $request->validate([
            'course_id' => 'required|integer|min:1',
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'xp_reward' => 'nullable|integer|min:0|max:1000',
        ]);

        $course = $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) $data['course_id']);

        $code = 'course_' . (int) $course->id . '_custom_' . bin2hex(random_bytes(6));

        $achievement = Achievement::query()->create([
            'code' => $code,
            'title' => trim((string) $data['title']),
            'description' => trim((string) ($data['description'] ?? '')),
            'xp_reward' => (int) ($data['xp_reward'] ?? 0),
            'is_system' => false,
            'course_id' => (int) $course->id,
            'created_by' => (int) $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Курсовая ачивка создана.',
            'achievement' => [
                'id' => (int) $achievement->id,
            ],
        ]);
    }

    public function deleteCourseAchievement(Request $request): JsonResponse
    {
        if (($request->user()->role ?? '') !== 'teacher') {
            abort(403);
        }

        $data = $request->validate([
            'achievement_id' => 'required|integer|min:1',
        ]);

        $achievement = Achievement::query()
            ->where('id', (int) $data['achievement_id'])
            ->where('is_system', false)
            ->firstOrFail();

        if ((int) ($achievement->created_by ?? 0) !== (int) $request->user()->id) {
            $this->courseAccess->findTeacherCourseOrFail($request->user(), (int) ($achievement->course_id ?? 0));
        }

        $achievement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Курсовая ачивка удалена.',
        ]);
    }

    private function handleCompleteLesson(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id' => 'required|integer|min:1|exists:lessons,id',
            'score_percent' => 'nullable|numeric|min:0|max:100',
            'step_results' => 'nullable|array',
            'step_results.*.step_id' => 'nullable|integer|min:1',
            'step_results.*.step_index' => 'nullable|integer|min:0',
            'step_results.*.is_correct' => 'nullable|boolean',
            'step_results.*.status' => 'nullable|string|max:32',
            'step_results.*.answer' => 'nullable',
        ]);

        $userId = (int) ($request->user()->id ?? 0);
        $lessonId = (int) $data['lesson_id'];
        $stepResults = collect((array) ($data['step_results'] ?? []));

        $validStepIds = DB::table('lesson_steps')
            ->where('lesson_id', $lessonId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $validStepMap = array_fill_keys($validStepIds, true);

        $taskCount = (int) $stepResults->count();
        $correctCount = (int) $stepResults->filter(fn ($item) => (bool) ($item['is_correct'] ?? false))->count();
        $scorePercent = isset($data['score_percent']) && is_numeric($data['score_percent'])
            ? round((float) $data['score_percent'], 2)
            : ($taskCount > 0 ? round(($correctCount / $taskCount) * 100, 2) : null);

        $attemptId = DB::transaction(function () use ($userId, $lessonId, $scorePercent, $stepResults, $validStepMap): int {
            $now = now();

            $attemptId = (int) DB::table('user_lesson_attempts')->insertGetId([
                'user_id' => $userId,
                'lesson_id' => $lessonId,
                'score_percent' => $scorePercent,
                'completed' => 1,
                'started_at' => $now,
                'finished_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $answerRows = [];
            foreach ($stepResults as $item) {
                $stepId = (int) ($item['step_id'] ?? 0);
                if ($stepId < 1 || !isset($validStepMap[$stepId])) {
                    continue;
                }

                $answerRows[] = [
                    'attempt_id' => $attemptId,
                    'step_id' => $stepId,
                    'answer_payload' => json_encode([
                        'answer' => $item['answer'] ?? null,
                        'status' => $item['status'] ?? null,
                        'step_index' => $item['step_index'] ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'is_correct' => (bool) ($item['is_correct'] ?? false),
                    'answered_at' => $now,
                ];
            }

            if (count($answerRows) > 0) {
                DB::table('user_step_answers')->insert($answerRows);
            }

            $courseId = (int) DB::table('lessons as l')
                ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
                ->where('l.id', $lessonId)
                ->value('cm.course_id');

            if ($courseId > 0) {
                $existing = DB::table('user_course_progress')
                    ->where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->whereNotNull('started_at')
                    ->first();

                if ($existing !== null) {
                    $completedLessons = (int) DB::table('user_lesson_attempts as ula')
                        ->join('lessons as l', 'l.id', '=', 'ula.lesson_id')
                        ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
                        ->where('ula.user_id', $userId)
                        ->where('ula.completed', 1)
                        ->where('cm.course_id', $courseId)
                        ->distinct('ula.lesson_id')
                        ->count('ula.lesson_id');

                    $courseLessonTotal = (int) DB::table('lessons as l')
                        ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
                        ->where('cm.course_id', $courseId)
                        ->count();

                    $progressPercent = $courseLessonTotal > 0
                        ? round(($completedLessons / $courseLessonTotal) * 100, 2)
                        : 0;
                    $xpEarned = max(0, $completedLessons * 50);

                    DB::table('user_course_progress')
                        ->where('user_id', $userId)
                        ->where('course_id', $courseId)
                        ->update([
                            'progress_percent' => $progressPercent,
                            'completed_lessons' => $completedLessons,
                            'xp_earned' => $xpEarned,
                            'completed_at' => $progressPercent >= 100 ? $now : null,
                            'updated_at' => $now,
                        ]);
                }
            }

            return $attemptId;
        });

        return response()->json([
            'success' => true,
            'attempt_id' => $attemptId,
        ]);
    }

    public function handle(Request $request): RedirectResponse|JsonResponse
    {
        $action = (string) $request->input('action', '');

        if ($action === 'complete_lesson') {
            return $this->handleCompleteLesson($request);
        }

        if (($request->user()->role ?? '') !== 'teacher') {
            return redirect()->route('teacher.indes');
        }

        $title = trim((string) $request->input('title', ''));

        if ($action === 'create_course') {
            if ($title === '') {
                return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                    ->with('student_status', 'Операция выполнена.');
            }

            Course::query()->create([
                'language_id' => $this->courseAccess->ensureDefaultLanguageId(),
                'title' => mb_substr($title, 0, 255),
                'status' => 'published',
                'visibility' => 'public',
                'created_by' => (int) $request->user()->id,
            ]);

            return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                ->with('student_status', 'Операция выполнена.');
        }

        if ($action === 'create_module') {
            $courseId = (int) $request->input('course_id', 0);
            if ($courseId < 1 || $title === '') {
                return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                    ->with('student_status', 'Операция выполнена.');
            }

            $course = $this->courseAccess->findTeacherCourseOrFail($request->user(), $courseId);
            $course->modules()->create([
                'title' => mb_substr($title, 0, 255),
                'order_num' => ((int) $course->modules()->max('order_num')) + 1,
            ]);

            return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                ->with('student_status', 'Операция выполнена.');
        }

        if ($action === 'create_lesson') {
            $courseId = (int) $request->input('course_id', 0);
            $moduleId = (int) $request->input('module_id', 0);
            if ($courseId < 1 || $moduleId < 1 || $title === '') {
                return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                    ->with('student_status', 'Операция выполнена.');
            }

            $course = $this->courseAccess->findTeacherCourseOrFail($request->user(), $courseId);
            $module = $course->modules()->where('id', $moduleId)->firstOrFail();

            $module->lessons()->create([
                'title' => mb_substr($title, 0, 255),
                'lesson_type' => 'standard',
                'status' => 'published',
                'order_num' => ((int) $module->lessons()->max('order_num')) + 1,
            ]);

            return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                ->with('student_status', 'Операция выполнена.');
        }

        return redirect()->route('teacher.indes');
    }
}
