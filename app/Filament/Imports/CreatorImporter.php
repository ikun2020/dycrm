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
            ImportColumn::make('nickname')->label(__('Nickname'))->requiredMapping()->rules(['required', 'max:255'])->example('skincare_creator'),
            ImportColumn::make('real_name')->label(__('Real Name'))->rules(['nullable', 'max:255']),
            ImportColumn::make('platform')->label(__('Platform'))->requiredMapping()->rules(['required', 'in:douyin,taobao,kuaishou,other'])->example('douyin'),
            ImportColumn::make('platform_uid')->label(__('Platform UID'))->rules(['nullable', 'max:255']),
            ImportColumn::make('homepage_url')->label(__('Homepage URL'))->rules(['nullable', 'url', 'max:255']),
            ImportColumn::make('phone')->label(__('Phone'))->rules(['nullable', 'max:255']),
            ImportColumn::make('wechat')->label(__('WeChat'))->rules(['nullable', 'max:255']),
            ImportColumn::make('region')->label(__('Region'))->rules(['nullable', 'max:255']),
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
            ImportColumn::make('owner')->label(__('Owner Email'))->relationship(resolveUsing: 'email'),
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
