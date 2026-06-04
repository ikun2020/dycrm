<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = "\u{5546}\u{54C1}\u{4E0E}\u{6837}\u{54C1}";
    protected static ?string $modelLabel = "\u{5546}\u{54C1}";
    protected static ?string $pluralModelLabel = "\u{5546}\u{54C1}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Product Info'))->columns(3)->schema([
                Forms\Components\TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
                Forms\Components\TextInput::make('brand')->label(__('Brand'))->maxLength(255),
                Forms\Components\TextInput::make('category')->label(__('Category'))->maxLength(255),
                Forms\Components\TextInput::make('sku')->label(__('SKU'))->maxLength(255),
                Forms\Components\TextInput::make('retail_price')->label(__('Retail Price'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('cost_price')->label(__('Cost Price'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label(__('Commission Rate'))->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('status')->label(__('Status'))->options(self::statusOptions())->default('active'),
                Forms\Components\Textarea::make('selling_points')->label(__('Selling Points'))->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->label(__('Notes'))->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('brand')->label(__('Brand'))->searchable(),
                Tables\Columns\TextColumn::make('category')->label(__('Category'))->searchable(),
                Tables\Columns\TextColumn::make('retail_price')->label(__('Retail Price'))->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')->label(__('Commission'))->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => __('Active'),
            'inactive' => __('Inactive'),
            'draft' => __('Draft'),
        ];
    }
}
