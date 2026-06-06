<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CreatorResource;
use App\Models\Creator;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FollowUpAlertWidget extends TableWidget
{
    protected static ?string $heading = 'BD 今日待跟进';

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
            ->actions([
                Action::make('openCreator')
                    ->label(__('Open Creator'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Creator $record): string => CreatorResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        return Creator::query()
            ->with('owner')
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now()->endOfDay())
            ->orderBy('next_follow_up_at');
    }
}
