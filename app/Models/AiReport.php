<?php

namespace App\Models;

use App\Support\OperationLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReport extends Model
{
    protected $fillable = [
        'creator_id',
        'report_type',
        'status',
        'score',
        'grade',
        'summary',
        'risk_points',
        'next_steps',
        'error_message',
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

    protected static function booted(): void
    {
        static::deleting(function (AiReport $report): void {
            $report->loadMissing('creator');

            OperationLogger::record('ai_report.deleted', $report->creator ?: $report, [
                'report_id' => $report->getKey(),
                'creator_id' => $report->creator_id,
                'creator' => $report->creator?->nickname,
                'score' => $report->score,
                'grade' => $report->grade,
                'status' => $report->status,
                'generated_at' => optional($report->generated_at)->format('Y-m-d H:i:s'),
            ], '删除 AI 报告');
        });
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
