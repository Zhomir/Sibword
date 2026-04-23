<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherCourseAccessService
{
    public function teacherCoursesQuery(User $user): Builder
    {
        $userId = (int) $user->id;

        return Course::query()
            ->where(function (Builder $query) use ($userId): void {
                $query->where('created_by', $userId)
                    ->orWhereHas('teachers', fn (Builder $q) => $q->where('users.id', $userId));
            });
    }

    public function findTeacherCourseOrFail(User $user, int $courseId): Course
    {
        return $this->teacherCoursesQuery($user)
            ->where('id', $courseId)
            ->firstOrFail();
    }

    public function ensureDefaultLanguageId(): int
    {
        $bxr = DB::table('languages')->where('code', 'bxr')->first();
        if ($bxr) {
            return (int) $bxr->id;
        }

        return (int) DB::table('languages')->insertGetId([
            'code' => 'bxr',
            'name' => 'Buryat language (Buryat)',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
