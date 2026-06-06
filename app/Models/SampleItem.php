<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SampleItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'sku',
        'unit_cost',
        'stock_quantity',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }
}
