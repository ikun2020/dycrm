<?php

namespace App\Filament\Widgets;

use App\Models\GmvRecord;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TeamPerformanceWidget extends TableWidget
{
    protected static ?string $heading = "\u{56E2}\u{961F}\u{4E1A}\u{7EE9}";
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Owner'))->searchable(),
                Tables\Columns\TextColumn::make('creators_count')->label(__('Creators'))->sortable(),
                Tables\Columns\TextColumn::make('follow_ups_count')->label(__('Follow-ups'))->sortable(),
                Tables\Columns\TextColumn::make('live_sessions_count')->label(__('Live Sessions'))->sortable(),
                Tables\Columns\TextColumn::make('month_gmv')
                    ->label(__('Monthly GMV'))
                    ->getStateUsing(fn (User $record): string => 'CNY '.number_format($this->monthlyGmvFor($record), 2)),
            ])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->withCount(['creators', 'followUps', 'liveSessions'])
            ->orderByDesc('creators_count');
    }

    private function monthlyGmvFor(User $user): float
    {
        return (float) GmvRecord::query()
            ->where('recorded_on', '>=', now()->startOfMonth()->toDateString())
            ->whereHas('creator', fn (Builder $query): Builder => $query->where('owner_id', $user->id))
            ->sum('gmv');
    }
}
