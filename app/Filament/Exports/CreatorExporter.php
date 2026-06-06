<?php

namespace App\Filament\Exports;

use App\Models\Creator;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CreatorExporter extends Exporter
{
    protected static ?string $model = Creator::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('platform')->label('平台'),
            ExportColumn::make('nickname')->label('达人昵称'),
            ExportColumn::make('agency_name')->label('MCN机构'),
            ExportColumn::make('region')->label('地区'),
            ExportColumn::make('creator_type')->label('达人类型'),
            ExportColumn::make('platform_uid')->label('UID'),
            ExportColumn::make('followers_count')->label('粉丝数'),
            ExportColumn::make('follower_tier')->label('粉丝量级'),
            ExportColumn::make('primary_category')->label('主营类型'),
            ExportColumn::make('reputation_score')->label('口碑分'),
            ExportColumn::make('avg_sales_amount')->label('场均销售额'),
            ExportColumn::make('daily_sales_amount')->label('日均销售额'),
            ExportColumn::make('avg_order_value')->label('客单价'),
            ExportColumn::make('male_fan_ratio')->label('男粉占比'),
            ExportColumn::make('female_fan_ratio')->label('女粉占比'),
            ExportColumn::make('gender_tendency')->label('性别倾向'),
            ExportColumn::make('province_overview')->label('省份概览'),
            ExportColumn::make('city_overview')->label('城市概览'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('Creator export completed. :count row(s) exported.', [
            'count' => $export->successful_rows,
        ]);
    }
}
