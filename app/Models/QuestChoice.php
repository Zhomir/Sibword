<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestChoice extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'node_id',
        'choice_text',
        'next_node_id',
        'order_num',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(QuestNode::class, 'node_id');
    }

    public function nextNode(): BelongsTo
    {
        return $this->belongsTo(QuestNode::class, 'next_node_id');
    }
}

