<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $fillable = [
        'creator_id',
        'user_id',
        'channel',
        'contact_person',
        'status_after',
        'content',
        'next_action',
        'contacted_at',
        'next_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
