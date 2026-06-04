<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sample extends Model
{
    protected $fillable = [
        'creator_id',
        'product_id',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
