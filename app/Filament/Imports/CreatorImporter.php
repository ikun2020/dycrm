<?php

namespace App\Filament\Imports;

use App\Models\Creator;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CreatorImporter extends Importer
{
    protected static ?string $model = Creator::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nickname')->label(__('Nickname Required'))->requiredMapping()->rules(['required', 'max:255'])->guess(['达人昵称', '达人昵称（必填）', '昵称', 'nickname'])->exampleHeader(__('Nickname Required'))->example('example_creator'),
            ImportColumn::make('platform')->label(__('Platform Required'))->requiredMapping()->rules(['required', 'max:255'])->guess(['平台', '平台（必填）', '平台（必填：抖音/小红书/视频号/快手/其他）', 'platform'])->exampleHeader(__('Platform Required'))->example(__('Douyin')),
            ImportColumn::make('platform_uid')->label(__('Platform UID'))->rules(['nullable', 'max:255'])->guess(['平台账号', '平台账号/UID', 'UID', '账号', '平台账号/手机号']),
            ImportColumn::make('phone')->label(__('Phone'))->rules(['nullable', 'max:255']),
            ImportColumn::make('wechat')->label(__('WeChat'))->rules(['nullable', 'max:255']),
            ImportColumn::make('agency_name')->label(__('Agency / Company'))->rules(['nullable', 'max:255']),
            ImportColumn::make('category')->label(__('Category'))->rules(['nullable', 'max:255']),
            ImportColumn::make('followers_count')->label(__('Followers'))->integer()->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('avg_viewers')->label(__('Average Viewers'))->integer()->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('avg_order_value')->label(__('Average Order Value'))->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('quote_fee')->label(__('Quote Fee'))->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('commission_rate')->label(__('Commission Rate'))->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0', 'max:100']),
            ImportColumn::make('cooperation_status')->label(__('Status'))->rules(['nullable', 'max:255'])->example('to_develop'),
            ImportColumn::make('tags')->label(__('Tags'))->array(',')->rules(['nullable', 'array'])->example('skincare,high-conversion'),
            ImportColumn::make('ai_score')->label(__('AI Score'))->integer()->rules(['nullable', 'integer', 'min:0', 'max:100']),
            ImportColumn::make('ai_grade')->label(__('Grade'))->rules(['nullable', 'max:10']),
            ImportColumn::make('ai_summary')->label(__('AI Summary'))->rules(['nullable', 'max:1000']),
            ImportColumn::make('notes')->label(__('Notes'))->rules(['nullable', 'max:2000']),
            ImportColumn::make('last_contacted_at')->label(__('Last Contacted At'))->rules(['nullable', 'date']),
            ImportColumn::make('next_follow_up_at')->label(__('Next Follow-up At'))->rules(['nullable', 'date']),
        ];
    }

    public function resolveRecord(): ?Creator
    {
        $this->data['platform'] = self::normalizePlatform($this->data['platform'] ?? null);
        $this->data['cooperation_status'] = self::normalizeStatus($this->data['cooperation_status'] ?? null);

        if (filled($this->data['platform_uid'] ?? null)) {
            return Creator::firstOrNew([
                'platform' => $this->data['platform'] ?? 'douyin',
                'platform_uid' => $this->data['platform_uid'],
            ]);
        }

        return new Creator();
    }

    private static function normalizePlatform(?string $platform): string
    {
        $platform = trim((string) $platform);

        return match (mb_strtolower($platform)) {
            'douyin', 'dy', '抖音', __('Douyin') => 'douyin',
            'xiaohongshu', 'xhs', '小红书', __('Xiaohongshu') => 'xiaohongshu',
            'shipinhao', 'sph', '视频号', __('Shipinhao') => 'shipinhao',
            'kuaishou', 'ks', '快手', __('Kuaishou') => 'kuaishou',
            'other', '其他', __('Other') => 'other',
            default => 'other',
        };
    }

    private static function normalizeStatus(?string $status): string
    {
        $status = trim((string) $status);

        return match (mb_strtolower($status)) {
            'to_develop', '待开发', __('To Develop') => 'to_develop',
            'contacted', '已联系', __('Contacted') => 'contacted',
            'communicating', '沟通中', __('Communicating') => 'communicating',
            'sample_sent', '已寄样', __('Sample Sent') => 'sample_sent',
            'scheduled', '已排期', __('Scheduled') => 'scheduled',
            'live', '直播中', __('Live') => 'live',
            'reviewed', '已复盘', __('Reviewed') => 'reviewed',
            'long_term', '长期合作', __('Long-term') => 'long_term',
            'paused', '暂停合作', __('Paused') => 'paused',
            'invalid', '无效达人', __('Invalid') => 'invalid',
            default => 'to_develop',
        };
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('Creator import completed. :count row(s) imported.', [
            'count' => $import->successful_rows,
        ]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.__(':count row(s) failed. Download the failure file for details.', [
                'count' => $failedRowsCount,
            ]);
        }

        return $body;
    }
}
