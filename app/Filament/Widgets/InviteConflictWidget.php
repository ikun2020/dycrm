<?php

namespace App\Filament\Widgets;

use App\Models\Creator;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class InviteConflictWidget extends TableWidget
{
    protected static ?string $heading = "\u{9080}\u{7EA6}\u{51B2}\u{7A81}\u{68C0}\u{67E5}";
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('platform')->label(__('Platform'))->formatStateUsing(fn (string $state): string => CreatorResourceStatus::platform($state))->badge(),
                Tables\Columns\TextColumn::make('platform_uid')->label(__('Platform UID'))->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
                Tables\Columns\TextColumn::make('cooperation_status')->label(__('Status'))->formatStateUsing(fn (string $state): string => CreatorResourceStatus::label($state))->badge(),
            ])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        $duplicateUidQuery = Creator::query()
            ->select('platform_uid')
            ->whereNotNull('platform_uid')
            ->where('platform_uid', '<>', '')
            ->groupBy('platform_uid')
            ->havingRaw('COUNT(*) > 1');

        return Creator::query()
            ->with('owner')
            ->whereIn('platform_uid', $duplicateUidQuery)
            ->orderBy('platform')
            ->orderBy('platform_uid')
            ->orderBy('id');
    }
}
