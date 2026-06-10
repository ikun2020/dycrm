<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeComment extends Model
{
    protected $fillable = [
        'knowledge_post_id',
        'user_id',
        'content',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeComment $comment): void {
            $comment->user_id ??= auth()->id();
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(KnowledgePost::class, 'knowledge_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
