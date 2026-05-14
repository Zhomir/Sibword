<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'xp_reward',
        'is_system',
        'course_id',
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements', 'achievement_id', 'user_id')
            ->withPivot('achieved_at');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
