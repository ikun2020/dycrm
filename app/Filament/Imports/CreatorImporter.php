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
            ImportColumn::make('nickname')->label('Nickname')->requiredMapping()->rules(['required', 'max:255'])->example('skincare_creator'),
            ImportColumn::make('real_name')->label('Real Name')->rules(['nullable', 'max:255']),
            ImportColumn::make('platform')->label('Platform')->requiredMapping()->rules(['required', 'in:douyin,taobao,kuaishou,other'])->example('douyin'),
            ImportColumn::make('platform_uid')->label('Platform UID')->rules(['nullable', 'max:255']),
            ImportColumn::make('homepage_url')->label('Homepage URL')->rules(['nullable', 'url', 'max:255']),
            ImportColumn::make('phone')->label('Phone')->rules(['nullable', 'max:255']),
            ImportColumn::make('wechat')->label('WeChat')->rules(['nullable', 'max:255']),
            ImportColumn::make('region')->label('Region')->rules(['nullable', 'max:255']),
            ImportColumn::make('agency_name')->label('Agency / Company')->rules(['nullable', 'max:255']),
            ImportColumn::make('category')->label('Category')->rules(['nullable', 'max:255']),
            ImportColumn::make('followers_count')->label('Followers')->integer()->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('avg_viewers')->label('Average Viewers')->integer()->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('avg_order_value')->label('Average Order Value')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('quote_fee')->label('Quote Fee')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('commission_rate')->label('Commission Rate')->numeric(decimalPlaces: 2)->rules(['nullable', 'numeric', 'min:0', 'max:100']),
            ImportColumn::make('cooperation_status')->label('Status')->rules(['nullable', 'max:255'])->example('to_develop'),
            ImportColumn::make('tags')->label('Tags')->array(',')->rules(['nullable', 'array'])->example('skincare,high-conversion'),
            ImportColumn::make('ai_score')->label('AI Score')->integer()->rules(['nullable', 'integer', 'min:0', 'max:100']),
            ImportColumn::make('ai_grade')->label('Grade')->rules(['nullable', 'max:10']),
            ImportColumn::make('ai_summary')->label('AI Summary')->rules(['nullable', 'max:1000']),
            ImportColumn::make('notes')->label('Notes')->rules(['nullable', 'max:2000']),
            ImportColumn::make('last_contacted_at')->label('Last Contacted At')->rules(['nullable', 'date']),
            ImportColumn::make('next_follow_up_at')->label('Next Follow-up At')->rules(['nullable', 'date']),
            ImportColumn::make('owner')->label('Owner Email')->relationship(resolveUsing: 'email'),
        ];
    }

    public function resolveRecord(): ?Creator
    {
        if (filled($this->data['platform_uid'] ?? null)) {
            return Creator::firstOrNew([
                'platform' => $this->data['platform'] ?? 'douyin',
                'platform_uid' => $this->data['platform_uid'],
            ]);
        }

        return new Creator();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Creator import completed. '.$import->successful_rows.' row(s) imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' row(s) failed. Download the failure file for details.';
        }

        return $body;
    }
}
