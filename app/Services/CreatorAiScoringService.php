<?php

namespace App\Services;

use App\Jobs\GenerateCreatorAiScore;
use App\Models\AiReport;
use App\Models\AiSetting;
use App\Models\Creator;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CreatorAiScoringService
{
    public function queueScore(Creator $creator, ?User $user = null, ?int $productId = null): AiReport
    {
        $report = AiReport::query()->create([
            'creator_id' => $creator->id,
            'report_type' => 'value_score',
            'status' => 'pending',
            'score' => 0,
            'summary' => 'AI 评分任务已提交，等待后台生成。',
            'raw_payload' => [
                'product_id' => $productId,
                'queued_at' => now()->toIso8601String(),
            ],
            'generated_by' => $user?->id,
            'generated_at' => now(),
        ]);

        GenerateCreatorAiScore::dispatch($report->id, $creator->id, $user?->id, $productId);

        return $report;
    }

    public function score(Creator $creator, ?User $user = null, ?int $productId = null): AiReport
    {
        return $this->scoreIntoReport($creator, $user, $productId);
    }

    public function scoreIntoReport(Creator $creator, ?User $user = null, ?int $productId = null, ?AiReport $report = null): AiReport
    {
        $setting = AiSetting::current();

        $this->validateSetting($setting);

        $products = $this->activeProducts($productId);

        $creator->loadMissing(['gmvRecords', 'liveSessions', 'samples.sampleItem', 'followUps']);

        $payload = $this->requestPayload($setting, $creator, $products);

        $response = $this->sendCompletionRequest($setting, $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->formatApiError($response));
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (blank($content)) {
            throw new RuntimeException('AI API 没有返回可解析的内容。');
        }

        return $this->storeResult($creator, (string) $content, $payload, $response->json(), $user, $report);
    }

    public function streamScore(Creator $creator, ?User $user, callable $onChunk, ?int $productId = null, ?callable $onStatus = null): AiReport
    {
        if ($onStatus !== null) {
            $onStatus('正在等待模型返回分析内容...');
        }

        $report = $this->score($creator, $user, $productId);

        $onChunk((string) data_get($report->raw_payload, 'content', $report->summary));

        return $report;
    }

    private function validateSetting(AiSetting $setting): void
    {
        if (! $setting->is_enabled) {
            throw new RuntimeException('AI 功能未启用，请先在 AI 设置中启用。');
        }

        if (blank($setting->api_key) || blank($setting->api_base_url) || blank($setting->model)) {
            throw new RuntimeException('AI API 地址、密钥或模型未配置完整。');
        }
    }

    private function activeProducts(?int $productId = null): Collection
    {
        $query = Product::query()->where('status', 'active');

        if ($productId !== null) {
            $query->whereKey($productId);
        } else {
            $query->latest('id')->limit(30);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            throw new RuntimeException($productId !== null
                ? '选择的商品不存在或未启用。'
                : '暂无启用状态的商品，请先维护商品资料。');
        }

        return $products;
    }

    private function storeResult(Creator $creator, string $content, array $payload, array $responsePayload, ?User $user = null, ?AiReport $report = null): AiReport
    {
        $result = $this->parseResult($content);
        $score = min(10, max(1, (int) ($result['score'] ?? 1)));
        $grade = $this->normalizeGrade((string) ($result['rating'] ?? $result['grade'] ?? ''), $score);
        $summary = trim((string) ($result['summary'] ?? $this->fallbackSummary($content)));
        $riskPoints = $this->stringifyList($result['risk_points'] ?? $result['risks'] ?? []);
        $nextSteps = $this->stringifyList($result['next_steps'] ?? $result['suggestions'] ?? []);
        $generatedAt = now();

        $creator->forceFill([
            'ai_score' => $score,
            'ai_grade' => $grade,
            'ai_summary' => $summary,
            'ai_scored_at' => $generatedAt,
        ])->save();

        $data = [
            'creator_id' => $creator->id,
            'report_type' => 'value_score',
            'status' => 'completed',
            'score' => $score,
            'grade' => $grade,
            'summary' => $summary,
            'risk_points' => $riskPoints,
            'next_steps' => $nextSteps,
            'error_message' => null,
            'raw_payload' => [
                'request' => $payload,
                'response' => $responsePayload,
                'content' => $content,
                'parsed' => $result,
            ],
            'generated_by' => $user?->id,
            'generated_at' => $generatedAt,
        ];

        if ($report) {
            $report->forceFill($data)->save();

            return $report->refresh();
        }

        return AiReport::query()->create($data);
    }

    private function requestPayload(AiSetting $setting, Creator $creator, Collection $products): array
    {
        $systemPrompt = $setting->system_prompt ?: '你是电商直播达人运营分析师，擅长根据达人画像、商品特点、履约记录和 GMV 数据判断达人是否适合带货。请输出清晰、可执行、偏商务 BD 视角的诊断。';

        return [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($creator, $products),
                ],
            ],
        ];
    }

    private function sendCompletionRequest(AiSetting $setting, array $payload): Response
    {
        $attempts = 2;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withToken((string) $setting->api_key)
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(10)
                    ->timeout(max(15, (int) $setting->timeout))
                    ->post($setting->normalizedBaseUrl().'/chat/completions', $payload);
            } catch (ConnectionException $exception) {
                if ($attempt < $attempts) {
                    usleep(1500000);

                    continue;
                }

                throw new RuntimeException($this->formatConnectionError($exception), previous: $exception);
            }

            if ($response->successful() || ! $this->shouldRetry($response) || $attempt === $attempts) {
                return $response;
            }

            usleep(1500000);
        }

        return $response;
    }

    private function shouldRetry(Response $response): bool
    {
        return in_array($response->status(), [429, 500, 502, 503, 504], true);
    }

    private function formatConnectionError(ConnectionException $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'cURL error 28')) {
            return 'AI 服务商连接超时，已自动重试但仍失败。当前网络到服务商不稳定，或服务商接口响应过慢，请稍后再试。';
        }

        return 'AI 服务商连接失败：'.$this->trimBody($message);
    }

    private function formatApiError(Response $response): string
    {
        $message = (string) data_get($response->json(), 'error.message', '');
        $code = (string) data_get($response->json(), 'error.code', '');

        if ($response->status() === 503 && (str_contains($message, 'memory overloaded') || $code === 'system_memory_overloaded')) {
            return 'AI 服务商当前资源过载，已自动重试但仍失败。请稍后再试，或在 AI 设置中切换更稳定的模型/服务商。';
        }

        if ($response->status() === 429) {
            return 'AI 服务请求过于频繁或额度受限，请稍后再试，或检查服务商限流/余额设置。';
        }

        return 'AI API 请求失败：'.$response->status().' '.$this->trimBody($response->body());
    }

    private function buildPrompt(Creator $creator, Collection $products): string
    {
        $productLines = $products->map(function (Product $product): array {
            return [
                'name' => $product->name,
                'brand' => $product->brand,
                'category' => $product->category,
                'retail_price' => (string) $product->retail_price,
                'commission_rate' => (string) $product->commission_rate,
                'selling_points' => $product->selling_points,
                'notes' => $product->notes,
            ];
        })->values()->all();

        $creatorData = [
            'nickname' => $creator->nickname,
            'platform' => $creator->platform,
            'platform_uid' => $creator->platform_uid,
            'category' => $creator->category,
            'followers_count' => $creator->followers_count,
            'avg_viewers' => $creator->avg_viewers,
            'avg_order_value' => (string) $creator->avg_order_value,
            'quote_fee' => (string) $creator->quote_fee,
            'commission_rate' => (string) $creator->commission_rate,
            'cooperation_status' => $creator->cooperation_status,
            'tags' => $creator->tags,
            'notes' => $creator->notes,
            'recent_gmv' => $creator->gmvRecords->sortByDesc('recorded_on')->take(5)->values()->map(fn ($record): array => [
                'date' => optional($record->recorded_on)->format('Y-m-d'),
                'gmv' => (string) $record->gmv,
                'orders_count' => $record->orders_count,
                'refund_amount' => (string) $record->refund_amount,
            ])->all(),
            'recent_live_sessions' => $creator->liveSessions->sortByDesc('starts_at')->take(5)->values()->map(fn ($session): array => [
                'starts_at' => optional($session->starts_at)->format('Y-m-d H:i'),
                'status' => $session->status,
                'script_notes' => $session->script_notes,
                'review_notes' => $session->review_notes,
            ])->all(),
            'recent_sample_shipments' => $creator->samples->sortByDesc('sent_at')->take(5)->values()->map(fn ($sample): array => [
                'sample' => $sample->sampleItem?->name ?: $sample->sample_name,
                'quantity' => $sample->quantity,
                'status' => $sample->status,
                'sent_at' => optional($sample->sent_at)->format('Y-m-d H:i'),
                'received_at' => optional($sample->received_at)->format('Y-m-d H:i'),
            ])->all(),
            'recent_follow_ups' => $creator->followUps->sortByDesc('contacted_at')->take(5)->values()->map(fn ($followUp): array => [
                'channel' => $followUp->channel,
                'content' => Str::limit((string) $followUp->content, 300),
                'next_action' => Str::limit((string) $followUp->next_action, 200),
            ])->all(),
        ];

        return implode("\n", [
            '请基于以下达人资料和当前选择的商品，输出多维度诊断分析。',
            '请先用自然语言分段分析：内容类目契合度、粉丝与客单价匹配、转化潜力、合作成本风险、履约风险、复购/长期合作潜力。',
            '最后必须单独输出一行 RESULT_JSON:{...}，用于系统保存结构化结果。',
            'RESULT_JSON 字段必须包含：score, rating, summary, risk_points, next_steps, dimensions。',
            'score 必须是 1-10 的整数；rating 必须三选一：高度契合、中度适配、不适配。',
            '',
            '达人资料：'.json_encode($creatorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            '',
            '当前商品：'.json_encode($productLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function parseResult(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $content) ?: $content;

        if (preg_match('/RESULT_JSON\s*:\s*(\{.*\})/su', $content, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/su', $content, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'score' => 1,
            'rating' => '不适配',
            'summary' => $this->fallbackSummary($content),
            'risk_points' => ['AI 返回内容未包含结构化结果'],
            'next_steps' => ['请重新生成评分或人工补充判断'],
        ];
    }

    private function normalizeGrade(string $grade, int $score): string
    {
        $grade = trim($grade);

        if (in_array($grade, ['高度契合', '中度适配', '不适配'], true)) {
            return $grade;
        }

        return match (true) {
            $score >= 8 => '高度契合',
            $score >= 5 => '中度适配',
            default => '不适配',
        };
    }

    private function stringifyList(mixed $value): ?string
    {
        if (is_array($value)) {
            return implode("\n", array_map(fn ($item): string => is_array($item)
                ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $item, $value));
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function fallbackSummary(string $content): string
    {
        $content = preg_replace('/RESULT_JSON\s*:\s*\{.*\}$/su', '', $content) ?: $content;
        $lines = collect(explode("\n", trim($content)))
            ->map(fn (string $line): string => trim($line, " \t\n\r\0\x0B-*#0123456789.、，"))
            ->filter()
            ->take(3);

        return Str::limit($lines->implode(' '), 240, '');
    }

    private function trimBody(string $body): string
    {
        return Str::limit(strip_tags($body), 500, '');
    }
}
