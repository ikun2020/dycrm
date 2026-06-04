<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GmvRecordResource\Pages;
use App\Models\GmvRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GmvRecordResource extends Resource
{
    protected static ?string $model = GmvRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = "\u{6392}\u{671F}\u{4E0E}\u{4E1A}\u{7EE9}";
    protected static ?string $modelLabel = "GMV\u{8BB0}\u{5F55}";
    protected static ?string $pluralModelLabel = "GMV\u{7EDF}\u{8BA1}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('GMV Data'))->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label(__('Creator'))->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label(__('Product'))->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\Select::make('live_session_id')->label(__('Live Session'))->relationship('liveSession', 'title')->searchable()->preload(),
                Forms\Components\DatePicker::make('recorded_on')->label(__('Recorded On'))->required(),
                Forms\Components\TextInput::make('gmv')->label(__('GMV'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('orders_count')->label(__('Orders'))->numeric()->default(0),
                Forms\Components\TextInput::make('refunds_count')->label(__('Refund Orders'))->numeric()->default(0),
                Forms\Components\TextInput::make('refund_amount')->label(__('Refund Amount'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_amount')->label(__('Commission Amount'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('slot_fee')->label(__('Slot Fee'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('sample_cost')->label(__('Sample Cost'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\Textarea::make('notes')->label(__('Notes'))->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recorded_on')->label(__('Date'))->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label(__('Product'))->searchable(),
                Tables\Columns\TextColumn::make('gmv')->label(__('GMV'))->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('orders_count')->label(__('Orders'))->numeric()->sortable(),
                Tables\Columns\TextColumn::make('slot_fee')->label(__('Slot Fee'))->money('CNY')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGmvRecords::route('/'),
            'create' => Pages\CreateGmvRecord::route('/create'),
            'edit' => Pages\EditGmvRecord::route('/{record}/edit'),
        ];
    }
}
