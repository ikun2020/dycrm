<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ChecksMenuPermission;
use App\Filament\Resources\SampleResource\Pages;
use App\Models\Sample;
use App\Models\SampleItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SampleResource extends Resource
{
    use ChecksMenuPermission;

    protected static ?string $model = Sample::class;

    protected static ?string $menuPermissionKey = 'samples';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = '商品与样品';

    protected static ?string $modelLabel = '寄样';

    protected static ?string $pluralModelLabel = '寄样管理';

    public static function getNavigationBadge(): ?string
    {
        $count = Sample::query()
            ->where(fn ($query) => $query
                ->where('status', 'pending')
                ->orWhereNull('status'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Sample Shipment Info'))
                ->description('记录样品寄送、签收和归属人，避免寄样履约遗漏。')
                ->icon('heroicon-o-truck')
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->components([
                    Forms\Components\Select::make('creator_id')->label(__('Creator'))->relationship('creator', 'nickname')->searchable()->preload()->required(),
                    Forms\Components\Select::make('sample_item_id')
                        ->label(__('Sample'))
                        ->relationship('sampleItem', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get): mixed => self::syncSampleCost($set, $get))
                        ->required(),
                    Forms\Components\TextInput::make('quantity')
                        ->label(__('Quantity'))
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, Get $get): mixed => self::syncSampleCost($set, $get)),
                    Forms\Components\TextInput::make('sample_cost')
                        ->label(__('Sample Cost'))
                        ->numeric()
                        ->prefix('CNY')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\Select::make('status')
                        ->label(__('Status'))
                        ->options(self::statusOptions())
                        ->default('pending')
                        ->afterStateHydrated(fn (Forms\Components\Select $component, ?string $state): mixed => blank($state) ? $component->state('pending') : null)
                        ->required()
                        ->selectablePlaceholder(false),
                    Forms\Components\Select::make('shipping_company')
                        ->label(__('Shipping Company'))
                        ->options(self::shippingCompanyOptions()),
                    Forms\Components\TextInput::make('tracking_number')->label(__('Tracking Number'))->maxLength(255),
                    Forms\Components\Select::make('owner_id')
                        ->label(__('Creator User'))
                        ->relationship('owner', 'name')
                        ->default(fn (): ?int => auth()->id())
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\DateTimePicker::make('sent_at')->label(__('Sent At'))->seconds(false),
                    Forms\Components\DateTimePicker::make('received_at')->label(__('Received At'))->seconds(false),
                    Forms\Components\Textarea::make('notes')->label(__('Notes'))->rows(4)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sampleItem.name')
                    ->label(__('Sample'))
                    ->formatStateUsing(fn (?string $state, Sample $record): string => $state ?: $record->sample_name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('quantity')->label(__('Quantity'))->sortable(),
                Tables\Columns\TextColumn::make('sample_cost')->label(__('Sample Cost'))->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusOptions()[$state ?: 'pending'] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'pending', null => 'danger',
                        'sent' => 'info',
                        'received' => 'success',
                        'returned' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'pending', null => 'heroicon-o-clock',
                        'sent' => 'heroicon-o-truck',
                        'received' => 'heroicon-o-check-circle',
                        'returned' => 'heroicon-o-arrow-uturn-left',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                Tables\Columns\TextColumn::make('shipping_company')->label(__('Shipping Company'))->searchable(),
                Tables\Columns\TextColumn::make('tracking_number')->label(__('Tracking Number'))->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Creator User'))->placeholder('-')->searchable(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated Date'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Status'))->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('shipping_company')->label(__('Shipping Company'))->options(self::shippingCompanyOptions()),
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
            'index' => Pages\ListSamples::route('/'),
            'create' => Pages\CreateSample::route('/create'),
            'edit' => Pages\EditSample::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => __('Pending Sample Shipment'),
            'sent' => __('Sample Shipped'),
            'received' => __('Sample Received'),
            'returned' => __('Sample Returned'),
        ];
    }

    public static function shippingCompanyOptions(): array
    {
        return [
            '顺丰' => '顺丰',
            '中通' => '中通',
            '申通' => '申通',
            '其他' => '其他',
        ];
    }

    public static function prepareShipmentData(array $data): array
    {
        $data['status'] = filled($data['status'] ?? null) ? $data['status'] : 'pending';

        if (! empty($data['sample_item_id'])) {
            $sampleItem = SampleItem::find($data['sample_item_id']);

            if ($sampleItem) {
                $data['sample_name'] = $sampleItem->name;
                $data['sample_cost'] = self::calculateSampleCost($sampleItem, $data['quantity'] ?? 1);
            }
        }

        return $data;
    }

    private static function syncSampleCost(Set $set, Get $get): void
    {
        $sampleItemId = $get('sample_item_id');

        if (blank($sampleItemId)) {
            $set('sample_cost', '0.00');

            return;
        }

        $sampleItem = SampleItem::find($sampleItemId);

        if (! $sampleItem) {
            $set('sample_cost', '0.00');

            return;
        }

        $set('sample_cost', self::calculateSampleCost($sampleItem, $get('quantity') ?: 1));
    }

    private static function calculateSampleCost(SampleItem $sampleItem, mixed $quantity): string
    {
        $quantity = max(1, (int) $quantity);
        $unitCost = (float) $sampleItem->unit_cost;

        return number_format($unitCost * $quantity, 2, '.', '');
    }
}
