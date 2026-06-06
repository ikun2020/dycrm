<?php

namespace App\Jobs;

use App\Models\AiReport;
use App\Models\Creator;
use App\Models\User;
use App\Services\CreatorAiScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class GenerateCreatorAiScore implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 360;

    public function __construct(
        public int $reportId,
        public int $creatorId,
        public ?int $userId = null,
        public ?int $productId = null,
    ) {}

    public function handle(CreatorAiScoringService $service): void
    {
        $report = AiReport::query()->findOrFail($this->reportId);

        $report->forceFill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        try {
            $service->scoreIntoReport(
                Creator::query()->findOrFail($this->creatorId),
                $this->userId ? User::query()->find($this->userId) : null,
                $this->productId,
                $report,
            );
        } catch (Throwable $exception) {
            $this->markFailed($exception);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception);
    }

    private function markFailed(Throwable $exception): void
    {
        AiReport::query()
            ->whereKey($this->reportId)
            ->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
                'generated_at' => now(),
            ]);
    }
}
