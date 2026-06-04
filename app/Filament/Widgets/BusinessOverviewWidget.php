<?php

namespace App\Filament\Widgets;

use App\Models\Creator;
use App\Models\GmvRecord;
use App\Models\LiveSession;
use App\Models\Sample;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        $monthGmv = GmvRecord::query()
            ->where('recorded_on', '>=', $monthStart)
            ->sum('gmv');

        return [
            Stat::make(__('Creators To Develop'), Creator::query()->where('cooperation_status', 'to_develop')->count())
                ->description(__('Invitation backlog'))
                ->color('warning'),
            Stat::make(__('Overdue Follow-ups'), Creator::query()->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())->count())
                ->description(__('Need immediate action'))
                ->color('danger'),
            Stat::make(__('Pending Samples'), Sample::query()->whereIn('status', ['pending', 'sent'])->count())
                ->description(__('Sample fulfillment in progress'))
                ->color('info'),
            Stat::make(__('Upcoming Lives'), LiveSession::query()->whereBetween('starts_at', [now(), now()->addDays(7)])->count())
                ->description(__('Next 7 days'))
                ->color('success'),
            Stat::make(__('Monthly GMV'), 'CNY '.number_format((float) $monthGmv, 2))
                ->description(__('Current month performance'))
                ->color('success'),
        ];
    }
}
