<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\DictionaryEntry;
use App\Models\ForumPost;
use App\Models\Lesson;
use App\Models\LessonStep;
use App\Models\ModerationAction;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherDashboardViewService
{
    public function __construct(
        private readonly TeacherCourseAccessService $courseAccess,
        private readonly LessonStepMapperService $stepMapper,
        private readonly StudentAchievementService $studentAchievementService,
        private readonly TeacherAnalyticsService $teacherAnalyticsService,
    ) {
    }

    public function build(Request $request): array
    {
        $role = (string) ($request->user()->role ?? 'student');
        $page = $request->query('page', $this->defaultPageForRole($role));

        $this->authorizePageAccess($request, $page);

        $selectedCourseIds = $role === 'student' ? $this->selectedCourseIdsForStudent($request) : [];
        if ($role === 'student') {
            $this->studentAchievementService->syncForUser((int) $request->user()->id);
        }
        [$curriculum, $lessons] = $this->buildCurriculumAndLessons($request, $page, $selectedCourseIds);
        if ($page === 'lesson_view') {
            $requestedLessonId = (int) $request->query('id', 0);
            if ($requestedLessonId > 0 && !isset($lessons[$requestedLessonId])) {
                $resolvedLesson = $this->resolveLessonForLessonView($request, $requestedLessonId, $selectedCourseIds);
                if ($resolvedLesson !== null) {
                    $lessons[$requestedLessonId] = $resolvedLesson;
                }
            }
        }
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

        $userProgress = $role === 'student'
            ? $this->studentProgressSummary((int) ($request->user()->id ?? 0))
            : ['completed_lessons' => 0, 'xp' => 0];

        $needsDictionary = in_array($page, ['teacher_panel'], true);
        $teacherCourseAchievements = $this->teacherCourseAchievementsPayload($request, $page);
        $lessonId = (int) $request->query('id', 1);

        $curriculumCourseIds = collect($curriculum['courses'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $availableCourseIds = collect($availableCourses)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ratingCourseIds = array_values(array_unique(array_merge($curriculumCourseIds, $availableCourseIds)));
        $courseRatings = $this->courseRatingsMap($ratingCourseIds);
        if ($role === 'student') {
            $availableCourses = collect($availableCourses)
                ->map(function (array $course) use ($courseRatings): array {
                    $stats = $courseRatings[(int) ($course['id'] ?? 0)] ?? ['avg' => 0, 'count' => 0];
                    $course['rating_avg'] = (float) ($stats['avg'] ?? 0);
                    $course['rating_count'] = (int) ($stats['count'] ?? 0);
                    return $course;
                })
                ->values()
                ->all();

            if ($page === 'student_catalog') {
                $catalogQuery = trim((string) $request->query('q', ''));
                $catalogSort = $this->normalizeCatalogSort((string) $request->query('sort', 'rating_desc'));
                $catalogLevel = trim((string) $request->query('level', ''));
                $catalogMinRating = $this->normalizeCatalogMinRating((string) $request->query('min_rating', '0'));

                $levelOptions = collect($availableCourses)
                    ->map(fn (array $course) => trim((string) ($course['level'] ?? '')))
                    ->filter(fn (string $level) => $level !== '')
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $availableCourses = collect($availableCourses)
                    ->when($catalogQuery !== '', function ($collection) use ($catalogQuery) {
                        $needle = mb_strtolower($catalogQuery);
                        return $collection->filter(function (array $course) use ($needle): bool {
                            $title = mb_strtolower((string) ($course['title'] ?? ''));
                            $description = mb_strtolower((string) ($course['description'] ?? ''));
                            $level = mb_strtolower((string) ($course['level'] ?? ''));
                            return str_contains($title, $needle)
                                || str_contains($description, $needle)
                                || str_contains($level, $needle);
                        });
                    })
                    ->when($catalogLevel !== '', function ($collection) use ($catalogLevel) {
                        return $collection->filter(fn (array $course): bool => (string) ($course['level'] ?? '') === $catalogLevel);
                    })
                    ->when($catalogMinRating > 0, function ($collection) use ($catalogMinRating) {
                        return $collection->filter(fn (array $course): bool => (float) ($course['rating_avg'] ?? 0) >= $catalogMinRating);
                    })
                    ->when($catalogSort === 'rating_desc', fn ($c) => $c->sortByDesc(fn ($item) => [($item['rating_avg'] ?? 0), ($item['rating_count'] ?? 0), ($item['title'] ?? '')]))
                    ->when($catalogSort === 'rating_asc', fn ($c) => $c->sortBy(fn ($item) => [($item['rating_avg'] ?? 0), ($item['rating_count'] ?? 0), ($item['title'] ?? '')]))
                    ->when($catalogSort === 'title_asc', fn ($c) => $c->sortBy(fn ($item) => ($item['title'] ?? '')))
                    ->when($catalogSort === 'title_desc', fn ($c) => $c->sortByDesc(fn ($item) => ($item['title'] ?? '')))
                    ->values()
                    ->all();

                $catalogMeta = [
                    'query' => $catalogQuery,
                    'sort' => $catalogSort,
                    'level' => $catalogLevel,
                    'min_rating' => $catalogMinRating,
                    'level_options' => $levelOptions,
                ];
            } else {
                $catalogMeta = [
                    'query' => '',
                    'sort' => 'rating_desc',
                    'level' => '',
                    'min_rating' => 0,
                    'level_options' => [],
                ];
            }
        } else {
            $catalogMeta = [
                'query' => '',
                'sort' => 'rating_desc',
                'level' => '',
                'min_rating' => 0,
                'level_options' => [],
            ];
        }
        $userCourseReviews = $role === 'student'
            ? $this->userCourseReviewsMap((int) $request->user()->id, $ratingCourseIds)
            : [];
        $teacherAnalytics = $this->teacherAnalyticsPayload($request, $page);
        $studentAchievements = $role === 'student'
            ? $this->studentAchievementService->achievementsPayload((int) $request->user()->id)
            : [];
        $studentAchievementProgress = $role === 'student'
            ? $this->studentAchievementService->progressPayload((int) $request->user()->id)
            : [];
        $studentCurrentCourseSummary = $role === 'student'
            ? $this->studentCurrentCourseSummary((int) ($request->user()->id ?? 0), $curriculum)
            : null;

        return [
            'page' => $page,
            'lessonId' => $lessonId,
            'dictionary' => $this->defaultDictionary($needsDictionary),
            'lessons' => $lessons,
            'curriculum' => $curriculum,
            'courseTitleMap' => $courseTitleMap,
            'moduleTitleMap' => $moduleTitleMap,
            'availableCourses' => $availableCourses,
            'userProgress' => $userProgress,
            'lessonForum' => $page === 'lesson_view'
                ? $this->lessonForumPayload($request, $lessonId)
                : ['lesson_id' => $lessonId, 'comments' => [], 'comments_page' => null, 'comments_total' => 0, 'comments_filter' => 'all', 'pinned_comment' => null],
            'courseRatings' => $courseRatings,
            'userCourseReviews' => $userCourseReviews,
            'teacherAnalytics' => $teacherAnalytics,
            'studentAchievements' => $studentAchievements,
            'studentAchievementProgress' => $studentAchievementProgress,
            'studentCurrentCourseSummary' => $studentCurrentCourseSummary,
            'catalogQuery' => (string) ($catalogMeta['query'] ?? ''),
            'catalogSort' => (string) ($catalogMeta['sort'] ?? 'rating_desc'),
            'catalogLevel' => (string) ($catalogMeta['level'] ?? ''),
            'catalogMinRating' => (int) ($catalogMeta['min_rating'] ?? 0),
            'catalogLevelOptions' => (array) ($catalogMeta['level_options'] ?? []),
            'teacherCourseAchievements' => $teacherCourseAchievements,
        ];
    }

    private function defaultPageForRole(string $role): string
    {
        return $role === 'teacher' ? 'teacher_home' : 'student_dashboard';
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

    private function authorizePageAccess(Request $request, string $page): void
    {
        $role = $request->user()?->role;
        $teacherOnlyPages = ['teacher_home', 'teacher_panel', 'teacher_analytics', 'teacher_courses', 'teacher_achievements'];

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
            ->whereNotNull('started_at')
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function studentProgressSummary(int $userId): array
    {
        if ($userId < 1) {
            return ['completed_lessons' => 0, 'xp' => 0];
        }

        $row = DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->whereNotNull('started_at')
            ->selectRaw('COALESCE(SUM(completed_lessons), 0) as completed_lessons_total, COALESCE(SUM(xp_earned), 0) as xp_total')
            ->first();

        return [
            'completed_lessons' => (int) ($row->completed_lessons_total ?? 0),
            'xp' => (int) ($row->xp_total ?? 0),
        ];
    }

    private function studentCurrentCourseSummary(int $userId, array $curriculum): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $progressRow = DB::table('user_course_progress as ucp')
            ->join('courses as c', 'c.id', '=', 'ucp.course_id')
            ->where('ucp.user_id', $userId)
            ->whereNotNull('ucp.started_at')
            ->where('c.status', '!=', 'archived')
            ->orderByDesc('ucp.updated_at')
            ->select([
                'ucp.course_id',
                'ucp.completed_lessons',
                'c.title',
            ])
            ->first();

        if ($progressRow === null) {
            return null;
        }

        $courseId = (int) ($progressRow->course_id ?? 0);
        $completedLessons = max(0, (int) ($progressRow->completed_lessons ?? 0));
        $courseTitle = trim((string) ($progressRow->title ?? 'Курс'));

        $courseData = collect($curriculum['courses'] ?? [])
            ->first(fn (array $course) => (int) ($course['id'] ?? 0) === $courseId);

        $totalLessons = 0;
        if (is_array($courseData)) {
            $totalLessons = (int) collect($courseData['modules'] ?? [])
                ->sum(fn (array $module) => count($module['lesson_ids'] ?? []));
        } else {
            $totalLessons = (int) DB::table('lessons as l')
                ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
                ->where('cm.course_id', $courseId)
                ->count();
        }

        $remainingLessons = max(0, $totalLessons - $completedLessons);

        return [
            'course_id' => $courseId,
            'course_title' => $courseTitle,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'remaining_lessons' => $remainingLessons,
        ];
    }

    private function availableCoursesForStudent(array $selectedCourseIds): array
    {
        return Course::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
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

    private function teacherAnalyticsPayload(Request $request, string $page): array
    {
        if (!in_array($page, ['teacher_panel', 'teacher_analytics'], true)) {
            return $this->teacherAnalyticsService->emptyPayload();
        }

        return $this->teacherAnalyticsService->buildForTeacher($request);
    }

    private function teacherCourseAchievementsPayload(Request $request, string $page): array
    {
        if ($page !== 'teacher_achievements') {
            return [];
        }

        $teacherCourseIds = $this->courseAccess
            ->teacherCoursesQuery($request->user())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($teacherCourseIds) === 0) {
            return [];
        }

        return Achievement::query()
            ->with('course:id,title')
            ->where('is_system', false)
            ->whereIn('course_id', $teacherCourseIds)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Achievement $a) => [
                'id' => (int) $a->id,
                'title' => (string) $a->title,
                'description' => (string) ($a->description ?? ''),
                'xp_reward' => (int) ($a->xp_reward ?? 0),
                'course_id' => $a->course_id !== null ? (int) $a->course_id : null,
                'course_title' => (string) ($a->course?->title ?? 'Курс'),
            ])
            ->all();
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
                    ->when($role === 'student', fn ($q) => $q->where('status', 'published'))
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
                'status' => (string) ($course->status ?? 'draft'),
                'visibility' => (string) ($course->visibility ?? 'private'),
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

    private function resolveLessonForLessonView(Request $request, int $lessonId, array $selectedCourseIds): ?array
    {
        $role = (string) ($request->user()->role ?? 'student');

        $query = Lesson::query()
            ->where('id', $lessonId)
            ->with(['module.course', 'steps' => fn ($q) => $q->orderBy('order_num')])
            ->withCount('steps');

        if ($role === 'student') {
            $query->where('status', 'published')
                ->whereHas('module.course', function ($q) {
                    $q->where('status', 'published');
                });
        } elseif ($role === 'teacher') {
            $teacherCourseIds = $this->courseAccess->teacherCoursesQuery($request->user())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $query->whereHas('module.course', fn ($q) => $q->whereIn('id', $teacherCourseIds));
        }

        $lesson = $query->first();
        if (!$lesson) {
            return null;
        }

        return [
            'title' => (string) $lesson->title,
            'course_id' => (int) ($lesson->module->course->id ?? 0),
            'module_id' => (int) $lesson->module_id,
            'steps' => $lesson->steps
                ->sortBy('order_num')
                ->values()
                ->map(fn (LessonStep $step) => $this->stepMapper->mapDbStepToFrontend($step))
                ->all(),
            'steps_count' => (int) ($lesson->steps_count ?? 0),
        ];
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
                'author_name' => (string) ($post->author->name ?? 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ'),
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
                    'author_name' => (string) ($pinnedPost->author->name ?? 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ'),
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
}
