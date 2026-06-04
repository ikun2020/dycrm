<?php

namespace App\Services;

use App\Models\AiReport;
use App\Models\AiSetting;
use App\Models\Creator;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CreatorAiScoringService
{
    public function score(Creator $creator, ?User $user = null, ?int $productId = null): AiReport
    {
        $setting = AiSetting::current();

        $this->validateSetting($setting);

        $products = $this->activeProducts($productId);

        $creator->loadMissing(['gmvRecords', 'liveSessions', 'samples', 'followUps']);

        $payload = $this->requestPayload($setting, $creator, $products);

        $response = Http::withToken((string) $setting->api_key)
            ->acceptJson()
            ->asJson()
            ->timeout($setting->timeout)
            ->post($setting->normalizedBaseUrl().'/chat/completions', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('AI API 请求失败：'.$response->status().' '.$response->body());
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (blank($content)) {
            throw new RuntimeException('AI API 没有返回可解析的内容。');
        }

        return $this->storeResult($creator, (string) $content, $payload, $response->json(), $user);
    }

    public function streamScore(Creator $creator, ?User $user, callable $onChunk, ?int $productId = null): AiReport
    {
        $setting = AiSetting::current();

        $this->validateSetting($setting);

        $products = $this->activeProducts($productId);
        $creator->loadMissing(['gmvRecords', 'liveSessions', 'samples', 'followUps']);

        $payload = $this->requestPayload($setting, $creator, $products);
        $payload['stream'] = true;

        $content = '';
        $rawResponse = '';
        $buffer = '';
        $httpCode = 0;

        $curl = curl_init($setting->normalizedBaseUrl().'/chat/completions');

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$setting->api_key,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => $setting->timeout,
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$content, &$rawResponse, &$buffer, $onChunk): int {
                $rawResponse .= $chunk;
                $buffer .= $chunk;

                while (($position = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $position));
                    $buffer = substr($buffer, $position + 1);

                    if ($line === '' || str_starts_with($line, 'event:')) {
                        continue;
                    }

                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, 5));

                    if ($data === '[DONE]') {
                        continue;
                    }

                    $json = json_decode($data, true);
                    $delta = data_get($json, 'choices.0.delta.content')
                        ?? data_get($json, 'choices.0.message.content')
                        ?? '';

                    if ($delta !== '') {
                        $content .= $delta;
                        $onChunk($delta);
                    }
                }

                return strlen($chunk);
            },
        ]);

        $success = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        curl_close($curl);

        if ($success === false) {
            throw new RuntimeException('AI API 流式请求失败：'.$error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('AI API 请求失败：'.$httpCode.' '.$rawResponse);
        }

        if (blank($content)) {
            $jsonResponse = json_decode($rawResponse, true);
            $content = (string) data_get($jsonResponse, 'choices.0.message.content', '');
        }

        if (blank($content)) {
            throw new RuntimeException('AI API 没有返回可解析的流式内容。');
        }

        return $this->storeResult($creator, $content, $payload, ['streamed_content' => $content], $user);
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

    private function activeProducts(?int $productId = null)
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

    private function storeResult(Creator $creator, string $content, array $payload, array $responsePayload, ?User $user = null): AiReport
    {
        $result = $this->parseResult($content);
        $score = min(10, max(1, (int) ($result['score'] ?? 1)));
        $grade = $this->normalizeGrade((string) ($result['rating'] ?? $result['grade'] ?? '不适配'), $score);
        $summary = trim((string) ($result['summary'] ?? ''));
        $riskPoints = $this->stringifyList($result['risk_points'] ?? $result['risks'] ?? []);
        $nextSteps = $this->stringifyList($result['next_steps'] ?? $result['suggestions'] ?? []);
        $generatedAt = now();

        $creator->forceFill([
            'ai_score' => $score,
            'ai_grade' => $grade,
            'ai_summary' => $summary,
            'ai_scored_at' => $generatedAt,
        ])->save();

        return AiReport::query()->create([
            'creator_id' => $creator->id,
            'report_type' => 'value_score',
            'score' => $score,
            'grade' => $grade,
            'summary' => $summary,
            'risk_points' => $riskPoints,
            'next_steps' => $nextSteps,
            'raw_payload' => [
                'request' => $payload,
                'response' => $responsePayload,
                'content' => $content,
                'parsed' => $result,
            ],
            'generated_by' => $user?->id,
            'generated_at' => $generatedAt,
        ]);
    }

    private function requestPayload(AiSetting $setting, Creator $creator, $products): array
    {
        $systemPrompt = $setting->system_prompt ?: '你是电商直播达人营销分析师，擅长根据达人画像、商品特点、履约和GMV数据判断达人是否适合带货。';

        return [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => $setting->max_tokens,
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

    private function buildPrompt(Creator $creator, $products): string
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
            'recent_follow_ups' => $creator->followUps->sortByDesc('contacted_at')->take(5)->values()->map(fn ($followUp): array => [
                'channel' => $followUp->channel,
                'content' => Str::limit((string) $followUp->content, 300),
                'next_action' => Str::limit((string) $followUp->next_action, 200),
            ])->all(),
        ];

        return "请基于以下达人资料和当前启用商品，像专业运营顾问一样实时输出多维度诊断分析。\n"
            ."请先用自然语言分段分析：内容类目契合度、粉丝与客单价匹配、转化潜力、合作成本风险、履约风险、复购/长期合作潜力。\n"
            ."最后单独输出一行 RESULT_JSON:{...}，用于系统保存结果。\n"
            ."RESULT_JSON 内字段必须包含：score, rating, summary, risk_points, next_steps, dimensions。\n"
            ."score 必须为 1-10 的整数，rating 必须三选一：高度契合、中度适配、不适配。\n\n"
            .'达人资料：'.json_encode($creatorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n"
            .'当前启用商品：'.json_encode($productLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

        throw new RuntimeException('AI 返回内容不是有效 JSON：'.$content);
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
}
