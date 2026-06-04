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
            ExportColumn::make('nickname')->label(__('Nickname')),
            ExportColumn::make('platform')->label(__('Platform')),
            ExportColumn::make('platform_uid')->label(__('Platform UID')),
            ExportColumn::make('phone')->label(__('Phone')),
            ExportColumn::make('wechat')->label(__('WeChat')),
            ExportColumn::make('agency_name')->label(__('Agency / Company')),
            ExportColumn::make('category')->label(__('Category')),
            ExportColumn::make('followers_count')->label(__('Followers')),
            ExportColumn::make('avg_viewers')->label(__('Average Viewers')),
            ExportColumn::make('avg_order_value')->label(__('Average Order Value')),
            ExportColumn::make('quote_fee')->label(__('Quote Fee')),
            ExportColumn::make('commission_rate')->label(__('Commission Rate')),
            ExportColumn::make('cooperation_status')->label(__('Status')),
            ExportColumn::make('tags')->label(__('Tags'))->formatStateUsing(fn (?array $state): string => implode(',', $state ?? [])),
            ExportColumn::make('ai_score')->label(__('AI Score')),
            ExportColumn::make('ai_grade')->label(__('Grade')),
            ExportColumn::make('ai_summary')->label(__('AI Summary')),
            ExportColumn::make('notes')->label(__('Notes')),
            ExportColumn::make('last_contacted_at')->label(__('Last Contacted At')),
            ExportColumn::make('next_follow_up_at')->label(__('Next Follow-up At')),
            ExportColumn::make('owner.name')->label(__('Owner')),
            ExportColumn::make('owner.email')->label(__('Owner Email')),
            ExportColumn::make('created_at')->label(__('Created At')),
            ExportColumn::make('updated_at')->label(__('Updated At')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('Creator export completed. :count row(s) exported.', [
            'count' => $export->successful_rows,
        ]);
    }
}
