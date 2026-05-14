<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'quest_id',
        'node_key',
        'body',
        'order_num',
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuestChoice::class, 'node_id')->orderBy('order_num');
    }
}

