<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\DictionaryEntry;
use App\Models\ForumPost;
use App\Models\ModerationAction;
use App\Models\LessonStep;
use App\Services\LessonStepMapperService;
use App\Services\StudentAchievementService;
use App\Services\TeacherAnalyticsService;
use App\Services\TeacherCourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherPrototypeController extends Controller
{
    public function __construct(
        private readonly TeacherCourseAccessService $courseAccess,
        private readonly LessonStepMapperService $stepMapper,
        private readonly StudentAchievementService $studentAchievementService,
        private readonly TeacherAnalyticsService $teacherAnalyticsService,
    ) {
    }

    private function defaultPageForRole(string $role): string
    {
        return $role === 'teacher' ? 'teacher_panel' : 'student_dashboard';
    }

    private function authorizePageAccess(Request $request, string $page): void
    {
        $role = $request->user()?->role;
        $teacherOnlyPages = ['teacher_panel', 'teacher_courses'];

        if (in_array($page, $teacherOnlyPages, true) && $role !== 'teacher') {
            abort(403);
        }
    }

    private function defaultDictionary(bool $forceLoad = false): array
    {
        if (!$forceLoad) {
            return [];
        }

        DictionaryEntry::seedDefaultsIfEmpty();

        return DictionaryEntry::query()
            ->orderBy('word')
            ->get()
            ->map(fn (DictionaryEntry $entry) => [
                'id' => (int) $entry->id,
                'word' => (string) $entry->word,
                'translation' => (string) $entry->translation,
                'transcription' => $entry->transcription,
            ])
            ->all();
    }

    private function selectedCourseIdsForStudent(Request $request): array
    {
        return DB::table('user_course_progress')
            ->where('user_id', (int) $request->user()->id)
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function availableCoursesForStudent(array $selectedCourseIds): array
    {
        return Course::query()
            ->where('status', 'published')
            ->when(count($selectedCourseIds) > 0, fn ($q) => $q->whereNotIn('id', $selectedCourseIds))
            ->withCount('modules')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course) => [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'description' => (string) ($course->description ?? ''),
                'level' => (string) ($course->level ?? ''),
                'modules_count' => (int) ($course->modules_count ?? 0),
            ])
            ->all();
    }

    private function courseRatingsMap(array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        if (count($courseIds) === 0) {
            return [];
        }

        return CourseReview::query()
            ->selectRaw('course_id, AVG(rating) as avg_rating, COUNT(*) as rating_count')
            ->whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->groupBy('course_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->course_id => [
                    'avg' => round((float) ($row->avg_rating ?? 0), 2),
                    'count' => (int) ($row->rating_count ?? 0),
                ],
            ])
            ->all();
    }

    private function userCourseReviewsMap(int $userId, array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        if ($userId < 1 || count($courseIds) === 0) {
            return [];
        }

        return CourseReview::query()
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->get()
            ->mapWithKeys(fn (CourseReview $review) => [
                (int) $review->course_id => [
                    'rating' => (int) $review->rating,
                    'review_text' => (string) ($review->review_text ?? ''),
                    'is_approved' => (bool) $review->is_approved,
                ],
            ])
            ->all();
    }

    private function syncStudentAchievements(int $userId): void
    {
        $this->studentAchievementService->syncForUser($userId);
    }

    private function studentAchievementsPayload(int $userId): array
    {
        return $this->studentAchievementService->achievementsPayload($userId);
    }

    private function studentAchievementProgressPayload(int $userId): array
    {
        return $this->studentAchievementService->progressPayload($userId);
    }

    private function teacherAnalyticsPayload(Request $request, string $page): array
    {
        if ($page !== 'teacher_panel') {
            return $this->teacherAnalyticsService->emptyPayload();
        }

        return $this->teacherAnalyticsService->buildForTeacher($request);
    }

    private function buildCurriculumAndLessons(Request $request, string $page, array $selectedCourseIds = []): array
    {
        $includeLessonSteps = $page === 'lesson_view';
        $role = $request->user()->role ?? 'student';
        $coursesQuery = $role === 'teacher'
            ? $this->courseAccess->teacherCoursesQuery($request->user())
            : Course::query()->whereIn('id', $selectedCourseIds)->where('status', '!=', 'archived');

        $courses = $coursesQuery
            ->with([
                'modules' => fn ($query) => $query->orderBy('order_num'),
                'modules.lessons' => fn ($query) => $query
                    ->orderBy('order_num')
                    ->withCount('steps'),
                ...($includeLessonSteps ? ['modules.lessons.steps' => fn ($query) => $query->orderBy('order_num')] : []),
            ])
            ->orderBy('id')
            ->get();

        $curriculum = ['courses' => []];
        $lessons = [];

        foreach ($courses as $course) {
            $coursePayload = [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'modules' => [],
            ];

            foreach ($course->modules as $module) {
                $lessonIds = [];

                foreach ($module->lessons as $lesson) {
                    $lessonIds[] = (int) $lesson->id;
                    $steps = $includeLessonSteps
                        ? $lesson->steps
                            ->sortBy('order_num')
                            ->values()
                            ->map(fn (LessonStep $step) => $this->stepMapper->mapDbStepToFrontend($step))
                            ->all()
                        : [];

                    $lessons[(int) $lesson->id] = [
                        'title' => (string) $lesson->title,
                        'course_id' => (int) $course->id,
                        'module_id' => (int) $module->id,
                        'steps' => $steps,
                        'steps_count' => (int) ($lesson->steps_count ?? count($steps)),
                    ];
                }

                $coursePayload['modules'][] = [
                    'id' => (int) $module->id,
                    'title' => (string) $module->title,
                    'lesson_ids' => $lessonIds,
                ];
            }

            $curriculum['courses'][] = $coursePayload;
        }

        return [$curriculum, $lessons];
    }

    private function lessonForumPayload(Request $request, int $lessonId): array
    {
        if ($lessonId < 1) {
            return [
                'lesson_id' => $lessonId,
                'comments' => [],
                'comments_page' => null,
                'comments_total' => 0,
            ];
        }

        $commentsFilter = (string) $request->query('comments_filter', 'all');
        if (!in_array($commentsFilter, ['all', 'mine', 'new'], true)) {
            $commentsFilter = 'all';
        }

        $baseQuery = ForumPost::query()
            ->where('is_hidden', false)
            ->whereHas('thread', fn ($query) => $query->where('lesson_id', $lessonId));

        if ($commentsFilter === 'mine') {
            $baseQuery->where('user_id', (int) ($request->user()->id ?? 0));
        }
        if ($commentsFilter === 'new') {
            $baseQuery->where('created_at', '>=', now()->subDays(7));
        }

        $commentsPage = $baseQuery
            ->with(['author:id,name,role'])
            ->withCount('likes')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'lesson_comments_page')
            ->withQueryString();

        $currentUserId = (int) ($request->user()->id ?? 0);
        $commentIds = $commentsPage->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $likedByMeMap = [];
        if ($currentUserId > 0 && count($commentIds) > 0) {
            $likedByMeMap = DB::table('forum_post_likes')
                ->where('user_id', $currentUserId)
                ->whereIn('post_id', $commentIds)
                ->pluck('post_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();
        }

        $comments = $commentsPage->getCollection()
            ->map(fn (ForumPost $post) => [
                'id' => (int) $post->id,
                'body' => (string) $post->body,
                'author_id' => (int) ($post->author_id ?? $post->user_id ?? 0),
                'author_name' => (string) ($post->author->name ?? 'Пользователь'),
                'author_role' => (string) ($post->author->role ?? 'student'),
                'created_at' => optional($post->created_at)->format('d.m.Y H:i'),
                'likes_count' => (int) ($post->likes_count ?? 0),
                'liked_by_me' => isset($likedByMeMap[(int) $post->id]),
            ])
            ->all();

        $pinnedAction = ModerationAction::query()
            ->where('entity_type', 'forum_post')
            ->where('status', 'pinned')
            ->whereIn('entity_id', function ($query) use ($lessonId): void {
                $query->select('forum_posts.id')
                    ->from('forum_posts')
                    ->join('forum_threads', 'forum_threads.id', '=', 'forum_posts.thread_id')
                    ->where('forum_threads.lesson_id', $lessonId);
            })
            ->orderByDesc('updated_at')
            ->first();

        $pinnedComment = null;
        if ($pinnedAction !== null) {
            $pinnedPost = ForumPost::query()
                ->where('id', (int) $pinnedAction->entity_id)
                ->where('is_hidden', false)
                ->with(['author:id,name,role'])
                ->first();

            if ($pinnedPost !== null) {
                $pinnedComment = [
                    'id' => (int) $pinnedPost->id,
                    'body' => (string) $pinnedPost->body,
                    'author_id' => (int) ($pinnedPost->author_id ?? $pinnedPost->user_id ?? 0),
                    'author_name' => (string) ($pinnedPost->author->name ?? 'Пользователь'),
                    'author_role' => (string) ($pinnedPost->author->role ?? 'student'),
                    'created_at' => optional($pinnedPost->created_at)->format('d.m.Y H:i'),
                ];
            }
        }

        return [
            'lesson_id' => $lessonId,
            'comments' => $comments,
            'comments_page' => $commentsPage,
            'comments_total' => (int) $commentsPage->total(),
            'comments_filter' => $commentsFilter,
            'pinned_comment' => $pinnedComment,
        ];
    }

    public function index(Request $request)
    {
        $role = (string) ($request->user()->role ?? 'student');
        $page = $request->query('page', $this->defaultPageForRole($role));

        $this->authorizePageAccess($request, $page);

        $selectedCourseIds = $role === 'student' ? $this->selectedCourseIdsForStudent($request) : [];
        if ($role === 'student') {
            $this->syncStudentAchievements((int) $request->user()->id);
        }
        [$curriculum, $lessons] = $this->buildCurriculumAndLessons($request, $page, $selectedCourseIds);
        $availableCourses = $role === 'student' ? $this->availableCoursesForStudent($selectedCourseIds) : [];

        $courseTitleMap = [];
        $moduleTitleMap = [];
        foreach (($curriculum['courses'] ?? []) as $course) {
            $courseId = (int) ($course['id'] ?? 0);
            if ($courseId > 0) {
                $courseTitleMap[$courseId] = (string) ($course['title'] ?? '');
            }
            foreach (($course['modules'] ?? []) as $module) {
                $moduleId = (int) ($module['id'] ?? 0);
                if ($moduleId > 0) {
                    $moduleTitleMap[$moduleId] = (string) ($module['title'] ?? '');
                }
            }
        }

        if (!$request->session()->has('user_progress')) {
            $request->session()->put('user_progress', ['completed_lessons' => 0, 'xp' => 0]);
        }

        $needsDictionary = in_array($page, ['teacher_panel'], true);
        $lessonId = (int) $request->query('id', 1);

        $curriculumCourseIds = collect($curriculum['courses'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $availableCourseIds = collect($availableCourses)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ratingCourseIds = array_values(array_unique(array_merge($curriculumCourseIds, $availableCourseIds)));
        $courseRatings = $this->courseRatingsMap($ratingCourseIds);
        $userCourseReviews = $role === 'student'
            ? $this->userCourseReviewsMap((int) $request->user()->id, $ratingCourseIds)
            : [];
        $teacherAnalytics = $this->teacherAnalyticsPayload($request, $page);
        $studentAchievements = $role === 'student'
            ? $this->studentAchievementsPayload((int) $request->user()->id)
            : [];
        $studentAchievementProgress = $role === 'student'
            ? $this->studentAchievementProgressPayload((int) $request->user()->id)
            : [];

        return view('teacher.indes', [
            'page' => $page,
            'lessonId' => $lessonId,
            'dictionary' => $this->defaultDictionary($needsDictionary),
            'lessons' => $lessons,
            'curriculum' => $curriculum,
            'courseTitleMap' => $courseTitleMap,
            'moduleTitleMap' => $moduleTitleMap,
            'availableCourses' => $availableCourses,
            'userProgress' => $request->session()->get('user_progress', ['completed_lessons' => 0, 'xp' => 0]),
            'lessonForum' => $page === 'lesson_view'
                ? $this->lessonForumPayload($request, $lessonId)
                : ['lesson_id' => $lessonId, 'comments' => [], 'comments_page' => null, 'comments_total' => 0, 'comments_filter' => 'all', 'pinned_comment' => null],
            'courseRatings' => $courseRatings,
            'userCourseReviews' => $userCourseReviews,
            'teacherAnalytics' => $teacherAnalytics,
            'studentAchievements' => $studentAchievements,
            'studentAchievementProgress' => $studentAchievementProgress,
        ]);
    }

    public function enrollCourse(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::query()->where('id', $courseId)->where('status', 'published')->firstOrFail();
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

        return redirect()->route('teacher.indes', ['page' => 'student_dashboard'])
            ->with('student_status', 'Операция выполнена.');
    }

    public function leaveCourse(Request $request, int $courseId): RedirectResponse
    {
        DB::table('user_course_progress')
            ->where('user_id', (int) $request->user()->id)
            ->where('course_id', $courseId)
            ->delete();

        return redirect()->route('teacher.indes', ['page' => 'student_dashboard'])
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

                $existing = DB::table('user_course_progress')
                    ->where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->first();

                DB::table('user_course_progress')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'course_id' => $courseId,
                    ],
                    [
                        'progress_percent' => $progressPercent,
                        'completed_lessons' => $completedLessons,
                        'xp_earned' => $xpEarned,
                        'started_at' => $existing?->started_at ?? $now,
                        'completed_at' => $progressPercent >= 100 ? $now : null,
                        'updated_at' => $now,
                        'created_at' => $existing?->created_at ?? $now,
                    ],
                );
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
                'status' => 'draft',
                'visibility' => 'private',
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
                'status' => 'draft',
                'order_num' => ((int) $module->lessons()->max('order_num')) + 1,
            ]);

            return redirect()->route('teacher.indes', ['page' => 'teacher_panel'])
                ->with('student_status', 'Операция выполнена.');
        }

        return redirect()->route('teacher.indes');
    }
}

