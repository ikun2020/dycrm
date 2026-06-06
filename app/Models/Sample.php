<?php

namespace App\Models;

use App\Support\OperationLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sample extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Sample $sample): void {
            $sample->loadMissing(['creator', 'sampleItem', 'owner']);

            OperationLogger::record('sample.deleted', $sample, [
                'creator_id' => $sample->creator_id,
                'creator' => $sample->creator?->nickname,
                'sample_item_id' => $sample->sample_item_id,
                'sample' => $sample->sampleItem?->name ?: $sample->sample_name,
                'quantity' => $sample->quantity,
                'status' => $sample->status,
                'owner_id' => $sample->owner_id,
                'owner' => $sample->owner?->name,
            ], __('Deleted sample shipment'));
        });
    }

    protected $fillable = [
        'creator_id',
        'product_id',
        'sample_item_id',
        'sample_name',
        'quantity',
        'sample_cost',
        'shipping_company',
        'tracking_number',
        'status',
        'sent_at',
        'received_at',
        'notes',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sample_cost' => 'decimal:2',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
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

    public function sampleItem(): BelongsTo
    {
        return $this->belongsTo(SampleItem::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
