<?php

namespace App\Filament\Widgets;

use App\Models\Sample;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FulfillmentMonitorWidget extends TableWidget
{
    protected static ?string $heading = "\u{6837}\u{54C1}\u{5C65}\u{7EA6}\u{76D1}\u{63A7}";
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('sample_name')->label(__('Sample'))->searchable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label(__('Product'))->searchable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge(),
                Tables\Columns\TextColumn::make('tracking_number')->label(__('Tracking Number'))->placeholder('-'),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
            ])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        return Sample::query()
            ->with(['creator', 'product', 'owner'])
            ->whereIn('status', ['pending', 'sent'])
            ->orderByRaw("FIELD(status, 'pending', 'sent')")
            ->orderByDesc('id');
    }
}
