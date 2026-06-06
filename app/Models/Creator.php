<?php

namespace App\Models;

use App\Support\OperationLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    protected static function booted(): void
    {
        static::saving(fn (Creator $creator): Creator => $creator->applyDefaults());

        static::deleting(function (Creator $creator): void {
            OperationLogger::record('creator.deleted', $creator, [
                'nickname' => $creator->nickname,
                'platform' => $creator->platform,
                'platform_uid' => $creator->platform_uid,
                'owner_id' => $creator->owner_id,
            ], '删除达人档案');
        });
    }

    protected $fillable = [
        'nickname',
        'platform',
        'platform_uid',
        'phone',
        'wechat',
        'region',
        'agency_name',
        'creator_type',
        'category',
        'followers_count',
        'follower_tier',
        'primary_category',
        'reputation_score',
        'avg_sales_amount',
        'daily_sales_amount',
        'avg_viewers',
        'avg_order_value',
        'male_fan_ratio',
        'female_fan_ratio',
        'gender_tendency',
        'province_overview',
        'city_overview',
        'quote_fee',
        'commission_rate',
        'cooperation_status',
        'tags',
        'ai_score',
        'ai_grade',
        'ai_summary',
        'ai_scored_at',
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
            'follower_tier' => 'string',
            'reputation_score' => 'decimal:2',
            'avg_sales_amount' => 'decimal:2',
            'daily_sales_amount' => 'decimal:2',
            'avg_viewers' => 'integer',
            'avg_order_value' => 'decimal:2',
            'male_fan_ratio' => 'decimal:4',
            'female_fan_ratio' => 'decimal:4',
            'quote_fee' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'ai_score' => 'integer',
            'ai_scored_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    private function applyDefaults(): self
    {
        $this->ai_score ??= 0;
        $this->followers_count ??= 0;
        $this->avg_viewers ??= 0;
        $this->avg_order_value ??= 0;
        $this->quote_fee ??= 0;
        $this->commission_rate ??= 0;
        $this->cooperation_status ??= 'to_develop';
        $this->platform ??= 'douyin';
        $this->category = $this->primary_category ?: $this->creator_type ?: $this->category;

        return $this;
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
