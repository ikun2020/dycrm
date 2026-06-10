<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ChecksMenuPermission;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    use ChecksMenuPermission;

    protected static ?string $model = Product::class;

    protected static ?string $menuPermissionKey = 'products';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = "\u{5546}\u{54C1}\u{7BA1}\u{7406}";

    protected static string|\UnitEnum|null $navigationGroup = '商品与样品';

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Product Info'))
                ->description('维护用于销售和达人匹配的商品资料、价格、佣金和卖点。')
                ->icon('heroicon-o-shopping-bag')
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->components([
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
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        'draft' => 'heroicon-o-document',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Status'))->options(self::statusOptions()),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record): bool => self::canEdit($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => self::canDeleteAny()),
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
