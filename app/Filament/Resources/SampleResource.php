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
    protected static ?string $navigationGroup = 'Products';
    protected static ?string $modelLabel = 'Sample';
    protected static ?string $pluralModelLabel = 'Samples';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Sample Info')->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label('Creator')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('Product')->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('sample_name')->label('Sample Name')->required()->maxLength(255),
                Forms\Components\TextInput::make('quantity')->label('Quantity')->numeric()->default(1),
                Forms\Components\TextInput::make('sample_cost')->label('Sample Cost')->numeric()->prefix('CNY')->default(0),
                Forms\Components\Select::make('status')->label('Status')->options(self::statusOptions())->default('pending'),
                Forms\Components\TextInput::make('shipping_company')->label('Shipping Company')->maxLength(255),
                Forms\Components\TextInput::make('tracking_number')->label('Tracking Number')->maxLength(255),
                Forms\Components\Select::make('owner_id')->label('Owner')->relationship('owner', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('sent_at')->label('Sent At')->seconds(false),
                Forms\Components\DateTimePicker::make('received_at')->label('Received At')->seconds(false),
                Forms\Components\Textarea::make('notes')->label('Notes')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sample_name')->label('Sample')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label('Creator')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('tracking_number')->label('Tracking Number')->searchable(),
                Tables\Columns\TextColumn::make('sent_at')->label('Sent At')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
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
            'pending' => 'Pending',
            'sent' => 'Sent',
            'received' => 'Received',
            'used' => 'Used',
            'returned' => 'Returned',
        ];
    }
}
