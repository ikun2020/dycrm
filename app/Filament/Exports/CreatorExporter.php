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
            ExportColumn::make('nickname')->label('Nickname'),
            ExportColumn::make('real_name')->label('Real Name'),
            ExportColumn::make('platform')->label('Platform'),
            ExportColumn::make('platform_uid')->label('Platform UID'),
            ExportColumn::make('homepage_url')->label('Homepage URL'),
            ExportColumn::make('phone')->label('Phone'),
            ExportColumn::make('wechat')->label('WeChat'),
            ExportColumn::make('region')->label('Region'),
            ExportColumn::make('agency_name')->label('Agency / Company'),
            ExportColumn::make('category')->label('Category'),
            ExportColumn::make('followers_count')->label('Followers'),
            ExportColumn::make('avg_viewers')->label('Average Viewers'),
            ExportColumn::make('avg_order_value')->label('Average Order Value'),
            ExportColumn::make('quote_fee')->label('Quote Fee'),
            ExportColumn::make('commission_rate')->label('Commission Rate'),
            ExportColumn::make('cooperation_status')->label('Status'),
            ExportColumn::make('tags')->label('Tags')->formatStateUsing(fn (?array $state): string => implode(',', $state ?? [])),
            ExportColumn::make('ai_score')->label('AI Score'),
            ExportColumn::make('ai_grade')->label('Grade'),
            ExportColumn::make('ai_summary')->label('AI Summary'),
            ExportColumn::make('notes')->label('Notes'),
            ExportColumn::make('last_contacted_at')->label('Last Contacted At'),
            ExportColumn::make('next_follow_up_at')->label('Next Follow-up At'),
            ExportColumn::make('owner.name')->label('Owner'),
            ExportColumn::make('owner.email')->label('Owner Email'),
            ExportColumn::make('created_at')->label('Created At'),
            ExportColumn::make('updated_at')->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Creator export completed. '.$export->successful_rows.' row(s) exported.';
    }
}
