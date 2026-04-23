<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonStep extends Model
{
    use HasFactory;

    protected $table = 'lesson_steps';

    protected $fillable = [
        'lesson_id',
        'step_type',
        'title',
        'prompt',
        'config_json',
        'order_num',
    ];

    protected $casts = [
        'config_json' => 'array',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}

