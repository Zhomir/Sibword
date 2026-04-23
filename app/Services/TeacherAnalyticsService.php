<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherAnalyticsService
{
    public function __construct(
        private readonly TeacherCourseAccessService $courseAccess,
    ) {
    }

    public function buildForTeacher(Request $request): array
    {
        $teacherCourses = $this->courseAccess->teacherCoursesQuery($request->user())
            ->select(['courses.id', 'courses.title'])
            ->orderBy('courses.title')
            ->get();

        $allCourseIds = $teacherCourses->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($allCourseIds) === 0) {
            return $this->emptyPayload();
        }

        $selectedCourseId = (int) $request->query('analytics_course_id', 0);
        if ($selectedCourseId > 0 && !in_array($selectedCourseId, $allCourseIds, true)) {
            $selectedCourseId = 0;
        }
        $courseIds = $selectedCourseId > 0 ? [$selectedCourseId] : $allCourseIds;

        $periodDays = (int) $request->query('analytics_period', 30);
        if (!in_array($periodDays, [7, 30], true)) {
            $periodDays = 30;
        }
        $since = now()->subDays($periodDays);

        $riskThreshold = (int) $request->query('analytics_risk_threshold', 40);
        if ($riskThreshold < 10 || $riskThreshold > 90) {
            $riskThreshold = 40;
        }

        $summaryRaw = DB::table('user_step_answers as usa')
            ->join('lesson_steps as ls', 'ls.id', '=', 'usa.step_id')
            ->join('lessons as l', 'l.id', '=', 'ls.lesson_id')
            ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
            ->whereIn('cm.course_id', $courseIds)
            ->where('usa.answered_at', '>=', $since)
            ->selectRaw('COUNT(*) as answers_total, SUM(CASE WHEN usa.is_correct = 0 THEN 1 ELSE 0 END) as wrong_total')
            ->first();

        $attemptsRaw = DB::table('user_lesson_attempts as ula')
            ->join('lessons as l', 'l.id', '=', 'ula.lesson_id')
            ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
            ->whereIn('cm.course_id', $courseIds)
            ->where('ula.created_at', '>=', $since)
            ->selectRaw('COUNT(*) as attempts_total, SUM(CASE WHEN ula.completed = 1 THEN 1 ELSE 0 END) as completed_total, AVG(ula.score_percent) as avg_score')
            ->first();

        $topSteps = DB::table('user_step_answers as usa')
            ->join('lesson_steps as ls', 'ls.id', '=', 'usa.step_id')
            ->join('lessons as l', 'l.id', '=', 'ls.lesson_id')
            ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
            ->join('courses as c', 'c.id', '=', 'cm.course_id')
            ->whereIn('cm.course_id', $courseIds)
            ->where('usa.answered_at', '>=', $since)
            ->selectRaw('
                usa.step_id,
                COALESCE(NULLIF(ls.title, ""), ls.step_type) as step_title,
                ls.step_type,
                l.title as lesson_title,
                c.title as course_title,
                COUNT(*) as answers_total,
                SUM(CASE WHEN usa.is_correct = 0 THEN 1 ELSE 0 END) as wrong_total,
                ROUND((SUM(CASE WHEN usa.is_correct = 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as error_percent
            ')
            ->groupBy('usa.step_id', 'ls.title', 'ls.step_type', 'l.title', 'c.title')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('error_percent')
            ->orderByDesc('wrong_total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'step_id' => (int) $row->step_id,
                'step_title' => (string) $row->step_title,
                'step_type' => (string) $row->step_type,
                'lesson_title' => (string) $row->lesson_title,
                'course_title' => (string) $row->course_title,
                'answers_total' => (int) $row->answers_total,
                'wrong_total' => (int) $row->wrong_total,
                'error_percent' => (float) $row->error_percent,
            ])
            ->all();

        $topLessons = DB::table('user_step_answers as usa')
            ->join('lesson_steps as ls', 'ls.id', '=', 'usa.step_id')
            ->join('lessons as l', 'l.id', '=', 'ls.lesson_id')
            ->join('course_modules as cm', 'cm.id', '=', 'l.module_id')
            ->join('courses as c', 'c.id', '=', 'cm.course_id')
            ->whereIn('cm.course_id', $courseIds)
            ->where('usa.answered_at', '>=', $since)
            ->selectRaw('
                l.id as lesson_id,
                l.title as lesson_title,
                c.title as course_title,
                COUNT(*) as answers_total,
                SUM(CASE WHEN usa.is_correct = 0 THEN 1 ELSE 0 END) as wrong_total,
                ROUND((SUM(CASE WHEN usa.is_correct = 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as error_percent
            ')
            ->groupBy('l.id', 'l.title', 'c.title')
            ->havingRaw('COUNT(*) >= 5')
            ->orderByDesc('error_percent')
            ->orderByDesc('wrong_total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'lesson_id' => (int) $row->lesson_id,
                'lesson_title' => (string) $row->lesson_title,
                'course_title' => (string) $row->course_title,
                'answers_total' => (int) $row->answers_total,
                'wrong_total' => (int) $row->wrong_total,
                'error_percent' => (float) $row->error_percent,
            ])
            ->all();

        $riskLessons = collect($topLessons)
            ->filter(fn ($lesson) => (float) ($lesson['error_percent'] ?? 0) >= $riskThreshold)
            ->values()
            ->all();

        $answersTotal = (int) ($summaryRaw->answers_total ?? 0);
        $wrongTotal = (int) ($summaryRaw->wrong_total ?? 0);

        return [
            'summary' => [
                'answers_total' => $answersTotal,
                'wrong_total' => $wrongTotal,
                'error_percent' => $answersTotal > 0 ? round(($wrongTotal / $answersTotal) * 100, 2) : 0.0,
                'attempts_total' => (int) ($attemptsRaw->attempts_total ?? 0),
                'completed_total' => (int) ($attemptsRaw->completed_total ?? 0),
                'avg_score' => round((float) ($attemptsRaw->avg_score ?? 0), 2),
            ],
            'top_steps' => $topSteps,
            'top_lessons' => $topLessons,
            'risk_lessons' => $riskLessons,
            'filters' => [
                'course_id' => $selectedCourseId,
                'period_days' => $periodDays,
                'risk_threshold' => $riskThreshold,
            ],
            'courses' => $teacherCourses->map(fn ($course) => [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
            ])->all(),
        ];
    }

    public function emptyPayload(): array
    {
        return [
            'summary' => [
                'answers_total' => 0,
                'wrong_total' => 0,
                'error_percent' => 0.0,
                'attempts_total' => 0,
                'completed_total' => 0,
                'avg_score' => 0.0,
            ],
            'top_steps' => [],
            'top_lessons' => [],
            'risk_lessons' => [],
            'filters' => [
                'course_id' => 0,
                'period_days' => 30,
                'risk_threshold' => 40,
            ],
            'courses' => [],
        ];
    }
}

