<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SampleResource\Pages;
use App\Models\Sample;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SampleResource extends Resource
{
    protected static ?string $model = Sample::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = "\u{5546}\u{54C1}\u{4E0E}\u{6837}\u{54C1}";
    protected static ?string $modelLabel = "\u{6837}\u{54C1}";
    protected static ?string $pluralModelLabel = "\u{6837}\u{54C1}\u{7BA1}\u{7406}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Sample Info'))->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label(__('Creator'))->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label(__('Product'))->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('sample_name')->label(__('Sample Name'))->required()->maxLength(255),
                Forms\Components\TextInput::make('quantity')->label(__('Quantity'))->numeric()->default(1),
                Forms\Components\TextInput::make('sample_cost')->label(__('Sample Cost'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\Select::make('status')->label(__('Status'))->options(self::statusOptions())->default('pending'),
                Forms\Components\TextInput::make('shipping_company')->label(__('Shipping Company'))->maxLength(255),
                Forms\Components\TextInput::make('tracking_number')->label(__('Tracking Number'))->maxLength(255),
                Forms\Components\Select::make('owner_id')->label(__('Owner'))->relationship('owner', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('sent_at')->label(__('Sent At'))->seconds(false),
                Forms\Components\DateTimePicker::make('received_at')->label(__('Received At'))->seconds(false),
                Forms\Components\Textarea::make('notes')->label(__('Notes'))->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sample_name')->label(__('Sample'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label(__('Product'))->searchable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge(),
                Tables\Columns\TextColumn::make('tracking_number')->label(__('Tracking Number'))->searchable(),
                Tables\Columns\TextColumn::make('sent_at')->label(__('Sent At'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Status'))->options(self::statusOptions()),
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
            'index' => Pages\ListSamples::route('/'),
            'create' => Pages\CreateSample::route('/create'),
            'edit' => Pages\EditSample::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => __('Pending'),
            'sent' => __('Sent'),
            'received' => __('Received'),
            'used' => __('Used'),
            'returned' => __('Returned'),
        ];
    }
}
