<?php

use App\Models\Creator;
use App\Services\CreatorAiScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::redirect('/', '/admin');

Route::get('/admin/creator-ai-diagnosis/creators', function (Request $request) {
    abort_unless(auth()->check(), 403);

    $keyword = trim((string) $request->query('q', ''));

    return Creator::query()
        ->when($keyword !== '', fn ($query) => $query->where(function ($query) use ($keyword): void {
            $query
                ->where('nickname', 'like', "%{$keyword}%")
                ->orWhere('platform_uid', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('wechat', 'like', "%{$keyword}%");
        }))
        ->orderByDesc('id')
        ->limit(5)
        ->get(['id', 'nickname'])
        ->map(fn (Creator $creator): array => [
            'id' => $creator->id,
            'label' => $creator->nickname,
        ]);
})->name('creator-ai-diagnosis.creators');

Route::post('/admin/creator-ai-diagnosis/run', function (Request $request) {
    abort_unless(auth()->check(), 403);

    $validated = $request->validate([
        'creator_id' => ['required', 'integer', 'exists:creators,id'],
        'product_id' => ['required', 'integer', 'exists:products,id'],
    ]);

    try {
        $report = app(CreatorAiScoringService::class)->score(
            Creator::query()->findOrFail($validated['creator_id']),
            auth()->user(),
            (int) $validated['product_id'],
        );

        return response()->json([
            'ok' => true,
            'content' => data_get($report->raw_payload, 'content', $report->summary),
            'score' => $report->score,
            'grade' => $report->grade,
            'summary' => $report->summary,
            'risk_points' => $report->risk_points,
            'next_steps' => $report->next_steps,
            'generated_at' => optional($report->generated_at)->format('Y-m-d H:i:s'),
        ]);
    } catch (\Throwable $exception) {
        return response()->json([
            'ok' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
})->name('creator-ai-diagnosis.run');

Route::post('/admin/creator-ai-diagnosis/stream', function (Request $request): StreamedResponse {
    abort_unless(auth()->check(), 403);

    $validated = $request->validate([
        'creator_id' => ['required', 'integer', 'exists:creators,id'],
        'product_id' => ['required', 'integer', 'exists:products,id'],
    ]);

    $creator = Creator::query()->findOrFail($validated['creator_id']);
    $productId = (int) $validated['product_id'];

    return response()->stream(function () use ($creator, $productId): void {
        $send = function (string $event, array $data): void {
            echo "event: {$event}\n";
            echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        };

        try {
            $send('status', ['message' => '正在整理达人资料、商品资料和历史数据...']);

            $report = app(CreatorAiScoringService::class)->streamScore(
                $creator,
                auth()->user(),
                function (string $chunk) use ($send): void {
                    $send('delta', ['content' => $chunk]);
                },
                $productId
            );

            $send('done', [
                'score' => $report->score,
                'grade' => $report->grade,
                'summary' => $report->summary,
                'generated_at' => optional($report->generated_at)->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $exception) {
            $send('error', ['message' => $exception->getMessage()]);
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'X-Accel-Buffering' => 'no',
    ]);
})->name('creator-ai-diagnosis.stream');
