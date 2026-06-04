<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReport extends Model
{
    protected $fillable = [
        'creator_id',
        'report_type',
        'score',
        'grade',
        'summary',
        'risk_points',
        'next_steps',
        'raw_payload',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'raw_payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
