<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'provider_name',
        'api_base_url',
        'api_key',
        'model',
        'timeout',
        'temperature',
        'max_tokens',
        'system_prompt',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'api_key' => 'encrypted',
            'timeout' => 'integer',
            'temperature' => 'decimal:2',
            'max_tokens' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'is_enabled' => false,
            'provider_name' => 'OpenAI Compatible',
            'api_base_url' => 'https://api.openai.com/v1',
            'model' => 'gpt-4o-mini',
            'timeout' => 60,
            'temperature' => 0.20,
            'max_tokens' => 1600,
        ]);
    }

    public function normalizedBaseUrl(): string
    {
        return rtrim((string) $this->api_base_url, '/');
    }
}
