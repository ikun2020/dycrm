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
            ImportColumn::make('platform')->label('平台')->requiredMapping()->rules(['required', 'max:255'])->guess(['平台', 'platform'])->example('抖音'),
            ImportColumn::make('nickname')->label('达人昵称')->requiredMapping()->rules(['required', 'max:255'])->guess(['达人昵称', '昵称', 'nickname'])->example('示例达人'),
            ImportColumn::make('agency_name')->label('MCN机构')->rules(['nullable', 'max:255'])->guess(['MCN机构', '机构', '公司']),
            ImportColumn::make('region')->label('地区')->rules(['nullable', 'max:255'])->guess(['地区', '区域']),
            ImportColumn::make('creator_type')->label('达人类型')->rules(['nullable', 'max:255'])->guess(['达人类型', '类型']),
            ImportColumn::make('platform_uid')->label('UID')->requiredMapping()->rules(['required', 'max:255'])->guess(['UID', '平台账号/UID', '平台账号', '账号', '抖音号']),
            ImportColumn::make('followers_count')->label('粉丝数')->integer()->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('follower_tier')->label('粉丝量级')->rules(['nullable', 'max:255']),
            ImportColumn::make('primary_category')->label('主营类型')->rules(['nullable', 'max:255']),
            ImportColumn::make('reputation_score')->label('口碑分')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('avg_sales_amount')->label('场均销售额')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('daily_sales_amount')->label('日均销售额')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('avg_order_value')->label('客单价')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('male_fan_ratio')->label('男粉占比')->numeric(decimalPlaces: 4)->rules(['nullable', 'numeric', 'min:0', 'max:1']),
            ImportColumn::make('female_fan_ratio')->label('女粉占比')->numeric(decimalPlaces: 4)->rules(['nullable', 'numeric', 'min:0', 'max:1']),
            ImportColumn::make('gender_tendency')->label('性别倾向')->rules(['nullable', 'max:255']),
            ImportColumn::make('province_overview')->label('省份概览')->rules(['nullable', 'max:2000']),
            ImportColumn::make('city_overview')->label('城市概览')->rules(['nullable', 'max:2000']),
        ];
    }

    public function resolveRecord(): ?Creator
    {
        $this->data['platform'] = self::normalizePlatform($this->data['platform'] ?? null);

        if (filled($this->data['platform_uid'] ?? null)) {
            return Creator::firstOrNew([
                'platform' => $this->data['platform'] ?? 'douyin',
                'platform_uid' => $this->data['platform_uid'],
            ]);
        }

        return new Creator;
    }

    private static function normalizePlatform(?string $platform): string
    {
        return match (mb_strtolower(trim((string) $platform))) {
            'douyin', 'dy', '抖音', __('Douyin') => 'douyin',
            'xiaohongshu', 'xhs', '小红书', __('Xiaohongshu') => 'xiaohongshu',
            'shipinhao', 'sph', '视频号', __('Shipinhao') => 'shipinhao',
            'kuaishou', 'ks', '快手', __('Kuaishou') => 'kuaishou',
            default => 'other',
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
