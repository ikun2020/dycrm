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
    protected static ?string $navigationGroup = '商品与样品';
    protected static ?string $modelLabel = '商品';
    protected static ?string $pluralModelLabel = '商品管理';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('商品信息')->columns(3)->schema([
                Forms\Components\TextInput::make('name')->label('商品名称')->required()->maxLength(255),
                Forms\Components\TextInput::make('brand')->label('品牌')->maxLength(255),
                Forms\Components\TextInput::make('category')->label('类目')->maxLength(255),
                Forms\Components\TextInput::make('sku')->label('SKU')->maxLength(255),
                Forms\Components\TextInput::make('retail_price')->label('零售价')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('cost_price')->label('成本价')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label('默认佣金')->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('status')->label('状态')->options([
                    'active' => '在售',
                    'paused' => '暂停',
                    'offline' => '下架',
                ])->default('active')->required(),
            ]),
            Forms\Components\Textarea::make('selling_points')->label('卖点')->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->label('备注')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('商品')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('brand')->label('品牌')->searchable(),
                Tables\Columns\TextColumn::make('category')->label('类目')->searchable(),
                Tables\Columns\TextColumn::make('retail_price')->label('零售价')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')->label('佣金')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状态')->options([
                    'active' => '在售',
                    'paused' => '暂停',
                    'offline' => '下架',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
