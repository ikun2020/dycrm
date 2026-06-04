<?php

namespace App\Filament\Widgets;

use App\Models\Creator;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FollowUpAlertWidget extends TableWidget
{
    protected static ?string $heading = "\u{8DDF}\u{8FDB}\u{9884}\u{8B66}";
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('cooperation_status')->label(__('Status'))->formatStateUsing(fn (string $state): string => CreatorResourceStatus::label($state))->badge(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label(__('Next Follow-up'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
            ])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        return Creator::query()
            ->with('owner')
            ->whereNotNull('next_follow_up_at')
            ->orderBy('next_follow_up_at');
    }
}
