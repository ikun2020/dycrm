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
    protected static ?string $navigationGroup = 'Products';
    protected static ?string $modelLabel = 'Product';
    protected static ?string $pluralModelLabel = 'Products';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product Info')->columns(3)->schema([
                Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(255),
                Forms\Components\TextInput::make('brand')->label('Brand')->maxLength(255),
                Forms\Components\TextInput::make('category')->label('Category')->maxLength(255),
                Forms\Components\TextInput::make('sku')->label('SKU')->maxLength(255),
                Forms\Components\TextInput::make('retail_price')->label('Retail Price')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('cost_price')->label('Cost Price')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label('Commission Rate')->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('status')->label('Status')->options(self::statusOptions())->default('active'),
                Forms\Components\Textarea::make('selling_points')->label('Selling Points')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->label('Notes')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('brand')->label('Brand')->searchable(),
                Tables\Columns\TextColumn::make('category')->label('Category')->searchable(),
                Tables\Columns\TextColumn::make('retail_price')->label('Retail Price')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')->label('Commission')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'draft' => 'Draft',
        ];
    }
}
