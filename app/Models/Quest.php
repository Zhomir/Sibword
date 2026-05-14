<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(QuestNode::class, 'quest_id');
    }
}

