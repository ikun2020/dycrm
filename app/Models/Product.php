<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'category',
        'sku',
        'retail_price',
        'cost_price',
        'commission_rate',
        'status',
        'selling_points',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'retail_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
        ];
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
}
