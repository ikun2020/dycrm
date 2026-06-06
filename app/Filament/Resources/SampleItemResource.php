<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ChecksMenuPermission;
use App\Filament\Resources\SampleItemResource\Pages;
use App\Models\SampleItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SampleItemResource extends Resource
{
    use ChecksMenuPermission;

    protected static ?string $model = SampleItem::class;

    protected static ?string $menuPermissionKey = 'sample-items';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = '商品与样品';

    protected static ?string $modelLabel = '样品';

    protected static ?string $pluralModelLabel = '样品管理';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Sample Item Info'))
                ->description('维护可寄出的样品资料、成本和库存，寄样记录会从这里选择样品。')
                ->icon('heroicon-o-beaker')
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->components([
                    Forms\Components\TextInput::make('name')->label(__('Sample Name'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('category')->label(__('Category'))->maxLength(255),
                    Forms\Components\TextInput::make('sku')->label(__('SKU'))->maxLength(255)->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('unit_cost')->label(__('Sample Cost'))->numeric()->prefix('CNY')->default(0),
                    Forms\Components\TextInput::make('stock_quantity')->label(__('Stock Quantity'))->numeric()->default(0),
                    Forms\Components\Select::make('status')->label(__('Status'))->options(self::statusOptions())->default('active'),
                    Forms\Components\Textarea::make('notes')->label(__('Notes'))->rows(4)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Sample Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label(__('Category'))->searchable(),
                Tables\Columns\TextColumn::make('sku')->label(__('SKU'))->searchable(),
                Tables\Columns\TextColumn::make('unit_cost')->label(__('Sample Cost'))->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')->label(__('Stock Quantity'))->sortable(),
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
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated At'))->dateTime('Y-m-d H:i')->sortable(),
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
            'index' => Pages\ListSampleItems::route('/'),
            'create' => Pages\CreateSampleItem::route('/create'),
            'edit' => Pages\EditSampleItem::route('/{record}/edit'),
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
