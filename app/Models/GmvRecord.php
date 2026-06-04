<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmvRecord extends Model
{
    protected $fillable = [
        'creator_id',
        'product_id',
        'live_session_id',
        'recorded_on',
        'gmv',
        'orders_count',
        'refunds_count',
        'refund_amount',
        'commission_amount',
        'slot_fee',
        'sample_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'gmv' => 'decimal:2',
            'orders_count' => 'integer',
            'refunds_count' => 'integer',
            'refund_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'slot_fee' => 'decimal:2',
            'sample_cost' => 'decimal:2',
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

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }
}
