<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSession extends Model
{
    protected $fillable = [
        'creator_id',
        'product_id',
        'title',
        'starts_at',
        'ends_at',
        'status',
        'slot_fee',
        'commission_rate',
        'pre_live_remind_at',
        'review_remind_at',
        'script_notes',
        'review_notes',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'slot_fee' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'pre_live_remind_at' => 'datetime',
            'review_remind_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function gmvRecords(): HasMany
    {
        return $this->hasMany(GmvRecord::class);
    }
}
