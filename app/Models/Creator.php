<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    protected $fillable = [
        'nickname',
        'real_name',
        'platform',
        'platform_uid',
        'homepage_url',
        'phone',
        'wechat',
        'region',
        'agency_name',
        'category',
        'followers_count',
        'avg_viewers',
        'avg_order_value',
        'quote_fee',
        'commission_rate',
        'cooperation_status',
        'tags',
        'ai_score',
        'ai_grade',
        'ai_summary',
        'notes',
        'last_contacted_at',
        'next_follow_up_at',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'followers_count' => 'integer',
            'avg_viewers' => 'integer',
            'avg_order_value' => 'decimal:2',
            'quote_fee' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'ai_score' => 'integer',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function gmvRecords(): HasMany
    {
        return $this->hasMany(GmvRecord::class);
    }

    public function aiReports(): HasMany
    {
        return $this->hasMany(AiReport::class);
    }
}
