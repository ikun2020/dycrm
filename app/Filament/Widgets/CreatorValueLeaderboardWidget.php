<?php

namespace App\Filament\Widgets;

use App\Models\Creator;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CreatorValueLeaderboardWidget extends TableWidget
{
    protected static ?string $heading = "\u{8FBE}\u{4EBA}\u{4EF7}\u{503C}\u{6392}\u{884C}\u{699C}";
    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('platform')->label(__('Platform'))->formatStateUsing(fn (string $state): string => CreatorResourceStatus::platform($state))->badge(),
                Tables\Columns\TextColumn::make('ai_score')->label(__('AI Score'))->sortable()->badge(),
                Tables\Columns\TextColumn::make('gmv_records_sum_gmv')
                    ->label(__('GMV'))
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
            ])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        return Creator::query()
            ->with('owner')
            ->withSum('gmvRecords', 'gmv')
            ->orderByDesc('gmv_records_sum_gmv')
            ->orderByDesc('ai_score');
    }
}
