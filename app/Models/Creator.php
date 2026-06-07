<?php

namespace App\Models;

use App\Support\OperationLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    private const IMPORT_FIELD_LABELS = [
        'nickname' => '达人昵称',
        'platform' => '平台',
        'platform_uid' => 'UID',
        'phone' => '手机号',
        'wechat' => '微信',
        'region' => '地区',
        'agency_name' => 'MCN机构',
        'creator_type' => '达人类型',
        'category' => '类目',
        'followers_count' => '粉丝数',
        'follower_tier' => '粉丝量级',
        'primary_category' => '主营类型',
        'reputation_score' => '口碑分',
        'avg_sales_amount' => '场均销售额',
        'daily_sales_amount' => '日均销售额',
        'avg_viewers' => '场均观看',
        'avg_order_value' => '客单价',
        'male_fan_ratio' => '男粉占比',
        'female_fan_ratio' => '女粉占比',
        'gender_tendency' => '性别倾向',
        'province_overview' => '省份概览',
        'city_overview' => '城市概览',
        'quote_fee' => '报价/坑位费',
        'commission_rate' => '佣金比例',
        'cooperation_status' => '状态',
        'tags' => '标签',
        'ai_score' => 'AI分数',
        'ai_grade' => '评级',
        'ai_summary' => 'AI摘要',
        'ai_scored_at' => 'AI评分时间',
        'notes' => '备注',
        'last_contacted_at' => '最近联系时间',
        'next_follow_up_at' => '下次跟进时间',
        'owner_id' => '负责人',
    ];

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

    public function getImportFieldLabel(string $field): string
    {
        return self::IMPORT_FIELD_LABELS[$field] ?? ucwords(str_replace(['_', '.'], ' ', $field));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getImportRules(): array
    {
        $rules = [];

        foreach ($this->getFillable() as $field) {
            $rules[$field] = ['nullable'];
        }

        $rules['nickname'] = ['required'];
        $rules['platform'] = ['required'];
        $rules['platform_uid'] = ['required'];

        return $rules;
    }

    private function applyDefaults(): self
    {
        $this->nickname = $this->stringOrNull($this->nickname) ?? '';
        $this->platform = $this->normalizePlatform($this->platform);
        $this->platform_uid = $this->stringOrNull($this->platform_uid);
        $this->creator_type = $this->stringOrNull($this->creator_type);
        $this->primary_category = $this->stringOrNull($this->primary_category);
        $this->category = $this->stringOrNull($this->category);
        $this->agency_name = $this->stringOrNull($this->agency_name);
        $this->region = $this->stringOrNull($this->region);
        $this->phone = $this->stringOrNull($this->phone);
        $this->wechat = $this->stringOrNull($this->wechat);
        $this->follower_tier = $this->stringOrNull($this->follower_tier);
        $this->gender_tendency = $this->stringOrNull($this->gender_tendency);
        $this->province_overview = $this->stringOrNull($this->province_overview);
        $this->city_overview = $this->stringOrNull($this->city_overview);
        $this->ai_grade = $this->stringOrNull($this->ai_grade);
        $this->ai_summary = $this->stringOrNull($this->ai_summary);
        $this->notes = $this->stringOrNull($this->notes);
        $this->followers_count = $this->integer($this->rawAttribute('followers_count'));
        $this->avg_viewers = $this->integer($this->rawAttribute('avg_viewers'));
        $this->reputation_score = $this->decimalOrNull($this->rawAttribute('reputation_score'));
        $this->avg_sales_amount = $this->decimalOrNull($this->rawAttribute('avg_sales_amount'));
        $this->daily_sales_amount = $this->decimalOrNull($this->rawAttribute('daily_sales_amount'));
        $this->avg_order_value = $this->decimalOrNull($this->rawAttribute('avg_order_value')) ?? 0;
        $this->male_fan_ratio = $this->ratioOrNull($this->rawAttribute('male_fan_ratio'));
        $this->female_fan_ratio = $this->ratioOrNull($this->rawAttribute('female_fan_ratio'));
        $this->quote_fee = $this->decimalOrNull($this->rawAttribute('quote_fee')) ?? 0;
        $this->commission_rate = $this->decimalOrNull($this->rawAttribute('commission_rate')) ?? 0;
        $this->cooperation_status = $this->normalizeStatus($this->cooperation_status);
        $this->tags = $this->tags($this->tags);
        $this->ai_score = max(0, min(10, $this->integer($this->rawAttribute('ai_score'))));
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

    private function rawAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    private function normalizePlatform(mixed $platform): string
    {
        return match (mb_strtolower(trim((string) $platform))) {
            'douyin', 'dy', '抖音' => 'douyin',
            'xiaohongshu', 'xhs', '小红书' => 'xiaohongshu',
            'shipinhao', 'sph', '视频号' => 'shipinhao',
            'kuaishou', 'ks', '快手' => 'kuaishou',
            default => 'other',
        };
    }

    private function normalizeStatus(mixed $status): string
    {
        return match (mb_strtolower(trim((string) $status))) {
            'contacted', '已触达', '已联系' => 'contacted',
            'communicating', '沟通中' => 'communicating',
            'sample_sent', '已寄样' => 'sample_sent',
            'scheduled', '已排期' => 'scheduled',
            'live', '直播中' => 'live',
            'reviewed', '已复盘' => 'reviewed',
            'long_term', '长期合作' => 'long_term',
            'paused', '暂停合作' => 'paused',
            'invalid', '无效达人' => 'invalid',
            default => 'to_develop',
        };
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '未公布' ? null : $value;
    }

    private function integer(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '未公布') {
            return 0;
        }

        if (str_contains($value, '亿')) {
            return max(0, (int) round((float) preg_replace('/[^\d.]/', '', $value) * 100000000));
        }

        if (str_contains($value, '万')) {
            return max(0, (int) round((float) preg_replace('/[^\d.]/', '', $value) * 10000));
        }

        return max(0, (int) round((float) preg_replace('/[^\d.]/', '', $value)));
    }

    private function decimalOrNull(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '未公布') {
            return null;
        }

        if (str_contains($value, '亿')) {
            return round((float) preg_replace('/[^\d.]/', '', $value) * 100000000, 2);
        }

        if (str_contains($value, '万')) {
            return round((float) preg_replace('/[^\d.]/', '', $value) * 10000, 2);
        }

        $value = preg_replace('/[^\d.]/', '', $value);

        return $value === '' ? null : (float) $value;
    }

    private function ratioOrNull(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '未公布') {
            return null;
        }

        $isPercent = str_contains($value, '%');
        $number = $this->decimalOrNull($value);

        if ($number === null) {
            return null;
        }

        if ($isPercent || $number > 1) {
            $number /= 100;
        }

        return min(1, max(0, $number));
    }

    /**
     * @return array<int, string>|null
     */
    private function tags(mixed $value): ?array
    {
        if (is_array($value)) {
            $tags = array_filter(array_map(fn (mixed $tag): string => trim((string) $tag), $value));

            return $tags === [] ? null : array_values($tags);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,，、]/u', $value) ?: [])));
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
