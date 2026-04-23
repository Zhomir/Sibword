<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'theory_content',
        'order_num',
        'lesson_type',
        'status',
        'estimated_minutes',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(LessonStep::class, 'lesson_id')->orderBy('order_num');
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'lesson_id')->latest('updated_at');
    }
}
