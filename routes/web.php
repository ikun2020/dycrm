<?php

use App\Models\AiReport;
use App\Models\Creator;
use App\Models\Sample;
use App\Notifications\SampleShipmentCreated;
use App\Services\CreatorAiScoringService;
use App\Support\ThemeColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::post('/admin/theme-color', function (Request $request) {
    abort_unless(auth()->check(), 403);

    $validated = $request->validate([
        'theme_color' => ['required', 'string', 'in:'.implode(',', array_keys(ThemeColor::options()))],
    ]);

    auth()->user()?->forceFill([
        'theme_color' => ThemeColor::normalize($validated['theme_color']),
    ])->save();

    return back();
})->middleware('throttle:30,1')->name('admin.theme-color.update');

Route::get('/admin/sample-shipment-badge', function () {
    abort_unless(auth()->check(), 403);
    abort_unless(auth()->user()?->canAccessMenu('samples'), 403);

    return response()->json([
        'count' => Sample::query()
            ->where(fn ($query) => $query
                ->where('status', 'pending')
                ->orWhereNull('status'))
            ->count(),
    ]);
})->middleware('throttle:120,1')->name('admin.sample-shipment-badge');

Route::get('/admin/sample-shipment-notifications/{sample}', function (Sample $sample) {
    abort_unless(auth()->check(), 403);
    abort_unless(auth()->user()?->canAccessMenu('samples'), 403);

    auth()->user()
        ?->unreadNotifications()
        ->where('type', SampleShipmentCreated::class)
        ->where('data->sample_id', $sample->id)
        ->get()
        ->each
        ->markAsRead();

    return redirect('/admin/samples/'.$sample->id.'/edit');
})->middleware('throttle:60,1')->name('admin.sample-shipment-notifications.show');

Route::get('/admin/creator-ai-diagnosis/creators', function (Request $request) {
    abort_unless(auth()->check(), 403);
    abort_unless(auth()->user()?->canAccessMenu('creators'), 403);

    $keyword = trim((string) $request->query('q', ''));
    $page = max(1, (int) $request->query('page', 1));
    $perPage = 5;

    $creators = Creator::query()
        ->when($keyword !== '', fn ($query) => $query->where('nickname', 'like', "%{$keyword}%"))
        ->orderByDesc('id')
        ->offset(($page - 1) * $perPage)
        ->limit($perPage + 1)
        ->get(['id', 'nickname'])
        ->values();

    $hasMore = $creators->count() > $perPage;

    return response()->json([
        'data' => $creators
            ->take($perPage)
            ->map(fn (Creator $creator): array => [
                'id' => $creator->id,
                'label' => $creator->nickname,
            ])
            ->values(),
        'has_more' => $hasMore,
        'next_page' => $hasMore ? $page + 1 : null,
    ]);
})->middleware('throttle:60,1')->name('creator-ai-diagnosis.creators');

Route::post('/admin/creator-ai-diagnosis/run', function (Request $request) {
    abort_unless(auth()->check(), 403);
    abort_unless(auth()->user()?->canEditMenu('creators') || auth()->user()?->canEditMenu('ai-reports'), 403);

    $validated = $request->validate([
        'creator_id' => ['required', 'integer', 'exists:creators,id'],
        'product_id' => ['required', 'integer', 'exists:products,id'],
    ]);

    $report = app(CreatorAiScoringService::class)->queueScore(
        Creator::query()->findOrFail($validated['creator_id']),
        auth()->user(),
        (int) $validated['product_id'],
    );

    return response()->json([
        'ok' => true,
        'queued' => true,
        'report_id' => $report->id,
        'status' => $report->status,
        'status_url' => route('creator-ai-diagnosis.reports.show', $report),
    ]);
})->middleware('throttle:10,1')->name('creator-ai-diagnosis.run');

Route::get('/admin/creator-ai-diagnosis/reports/{report}', function (AiReport $report) {
    abort_unless(auth()->check(), 403);
    abort_unless(auth()->user()?->canAccessMenu('ai-reports') || auth()->user()?->canAccessMenu('creators'), 403);

    return response()->json([
        'ok' => true,
        'status' => $report->status,
        'message' => $report->error_message,
        'content' => $report->status === 'completed' ? data_get($report->raw_payload, 'content', $report->summary) : null,
        'score' => $report->status === 'completed' ? $report->score : null,
        'grade' => $report->status === 'completed' ? $report->grade : null,
        'summary' => $report->status === 'completed' ? $report->summary : null,
        'risk_points' => $report->status === 'completed' ? $report->risk_points : null,
        'next_steps' => $report->status === 'completed' ? $report->next_steps : null,
        'generated_at' => optional($report->generated_at)->format('Y-m-d H:i:s'),
    ]);
})->middleware('throttle:120,1')->name('creator-ai-diagnosis.reports.show');
