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
    protected static ?string $navigationGroup = 'Schedule & GMV';
    protected static ?string $modelLabel = 'GMV Record';
    protected static ?string $pluralModelLabel = 'GMV Records';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('GMV Data')->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label('Creator')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('Product')->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\Select::make('live_session_id')->label('Live Session')->relationship('liveSession', 'title')->searchable()->preload(),
                Forms\Components\DatePicker::make('recorded_on')->label('Recorded On')->required(),
                Forms\Components\TextInput::make('gmv')->label('GMV')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('orders_count')->label('Orders')->numeric()->default(0),
                Forms\Components\TextInput::make('refunds_count')->label('Refund Orders')->numeric()->default(0),
                Forms\Components\TextInput::make('refund_amount')->label('Refund Amount')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_amount')->label('Commission Amount')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('slot_fee')->label('Slot Fee')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('sample_cost')->label('Sample Cost')->numeric()->prefix('CNY')->default(0),
                Forms\Components\Textarea::make('notes')->label('Notes')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recorded_on')->label('Date')->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label('Creator')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('gmv')->label('GMV')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('orders_count')->label('Orders')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('slot_fee')->label('Slot Fee')->money('CNY')->sortable(),
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
