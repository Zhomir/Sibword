<?php

namespace App\Services;

use App\Models\Achievement;
use Illuminate\Support\Facades\DB;

class StudentAchievementService
{
    public function syncForUser(int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        $this->ensureDefaultAchievements();

        $enrolledCourses = (int) DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->count();

        $completedLessons = (int) DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->sum('completed_lessons');

        $xpEarned = (int) DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->sum('xp_earned');

        $completedAttempts = (int) DB::table('user_lesson_attempts')
            ->where('user_id', $userId)
            ->where('completed', true)
            ->count();

        $commentsCount = (int) DB::table('forum_posts')
            ->where('user_id', $userId)
            ->count();

        $rules = [
            'first_course_enroll' => $enrolledCourses >= 1,
            'first_lesson_complete' => ($completedLessons >= 1) || ($completedAttempts >= 1),
            'lessons_5_complete' => ($completedLessons >= 5) || ($completedAttempts >= 5),
            'xp_500' => $xpEarned >= 500,
            'first_comment' => $commentsCount >= 1,
        ];

        $achievementsByCode = Achievement::query()
            ->whereIn('code', array_keys($rules))
            ->get()
            ->keyBy('code');

        foreach ($rules as $code => $passed) {
            if (!$passed) {
                continue;
            }

            $achievement = $achievementsByCode->get($code);
            if ($achievement === null) {
                continue;
            }

            DB::table('user_achievements')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'achievement_id' => (int) $achievement->id,
                ],
                [
                    'achieved_at' => now(),
                ],
            );
        }

        $this->syncCourseCompletionAchievements($userId);
    }

    public function achievementsPayload(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        return Achievement::query()
            ->join('user_achievements as ua', 'ua.achievement_id', '=', 'achievements.id')
            ->leftJoin('courses', 'courses.id', '=', 'achievements.course_id')
            ->where('ua.user_id', $userId)
            ->orderByDesc('ua.achieved_at')
            ->select([
                'achievements.code',
                'achievements.title',
                'achievements.description',
                'achievements.xp_reward',
                'achievements.is_system',
                'achievements.course_id',
                'courses.title as course_title',
                'ua.achieved_at',
            ])
            ->get()
            ->map(fn ($row) => [
                'code' => (string) $row->code,
                'title' => (string) $row->title,
                'description' => (string) ($row->description ?? ''),
                'xp_reward' => (int) ($row->xp_reward ?? 0),
                'is_system' => (bool) ($row->is_system ?? true),
                'course_id' => $row->course_id !== null ? (int) $row->course_id : null,
                'course_title' => $row->course_title !== null ? (string) $row->course_title : null,
                'achieved_at' => $row->achieved_at
                    ? \Illuminate\Support\Carbon::parse((string) $row->achieved_at)->format('d.m.Y H:i')
                    : null,
            ])
            ->all();
    }

    public function progressPayload(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        $enrolledCourses = (int) DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->count();

        $completedLessons = (int) DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->sum('completed_lessons');

        $xpEarned = (int) DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->sum('xp_earned');

        $completedAttempts = (int) DB::table('user_lesson_attempts')
            ->where('user_id', $userId)
            ->where('completed', true)
            ->count();

        $commentsCount = (int) DB::table('forum_posts')
            ->where('user_id', $userId)
            ->count();

        $effectiveLessons = max($completedLessons, $completedAttempts);

        return [
            [
                'title' => 'Первый курс',
                'current' => min($enrolledCourses, 1),
                'target' => 1,
                'unit' => 'курс',
            ],
            [
                'title' => 'Первый завершенный урок',
                'current' => min($effectiveLessons, 1),
                'target' => 1,
                'unit' => 'урок',
            ],
            [
                'title' => '5 завершенных уроков',
                'current' => min($effectiveLessons, 5),
                'target' => 5,
                'unit' => 'уроков',
            ],
            [
                'title' => '500 XP',
                'current' => min($xpEarned, 500),
                'target' => 500,
                'unit' => 'XP',
            ],
            [
                'title' => 'Первый комментарий',
                'current' => min($commentsCount, 1),
                'target' => 1,
                'unit' => 'комментарий',
            ],
        ];
    }

    private function ensureDefaultAchievements(): void
    {
        $defaults = [
            [
                'code' => 'first_course_enroll',
                'title' => 'Первый курс',
                'description' => 'Вы записались на первый курс.',
                'xp_reward' => 25,
            ],
            [
                'code' => 'first_lesson_complete',
                'title' => 'Первый шаг к цели',
                'description' => 'Вы завершили первый урок.',
                'xp_reward' => 50,
            ],
            [
                'code' => 'lessons_5_complete',
                'title' => 'Устойчивый прогресс',
                'description' => 'Вы завершили 5 уроков.',
                'xp_reward' => 100,
            ],
            [
                'code' => 'xp_500',
                'title' => 'Стабильная практика',
                'description' => 'Вы набрали 500 XP.',
                'xp_reward' => 120,
            ],
            [
                'code' => 'first_comment',
                'title' => 'Участник обсуждения',
                'description' => 'Вы оставили первый комментарий к уроку.',
                'xp_reward' => 30,
            ],
        ];

        foreach ($defaults as $item) {
            Achievement::query()->updateOrCreate(
                ['code' => (string) $item['code']],
                [
                    'title' => (string) $item['title'],
                    'description' => (string) ($item['description'] ?? ''),
                    'xp_reward' => (int) ($item['xp_reward'] ?? 0),
                    'is_system' => true,
                    'course_id' => null,
                    'created_by' => null,
                ],
            );
        }
    }

    private function syncCourseCompletionAchievements(int $userId): void
    {
        $completedCourseIds = DB::table('user_course_progress')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($completedCourseIds) === 0) {
            return;
        }

        $courseAchievements = Achievement::query()
            ->where('is_system', false)
            ->whereIn('course_id', $completedCourseIds)
            ->get(['id']);

        foreach ($courseAchievements as $achievement) {
            DB::table('user_achievements')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'achievement_id' => (int) $achievement->id,
                ],
                [
                    'achieved_at' => now(),
                ],
            );
        }
    }
}
