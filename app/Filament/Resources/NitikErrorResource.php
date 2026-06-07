<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NitikErrorResource\Pages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kholil\Nitik\Models\NitikError;

class NitikErrorResource extends Resource
{
    protected static ?string $model = NitikError::class;

    protected static ?string $slug = 'system/error-logs';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    protected static string|\UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $modelLabel = '错误日志';

    protected static ?string $pluralModelLabel = '错误日志';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('exception_class')
                    ->label('异常类型')
                    ->badge()
                    ->color('danger')
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('错误信息')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (NitikError $record): string => $record->message)
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => self::levelColor($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('count')
                    ->label('次数')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 10 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('最近出现')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_resolved')
                    ->label('已解决')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('级别')
                    ->options([
                        'ERROR' => 'Error',
                        'CRITICAL' => 'Critical',
                        'EMERGENCY' => 'Emergency',
                        'WARNING' => 'Warning',
                    ]),
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('已解决'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('查看'),
                    Action::make('markResolved')
                        ->label('标记已解决')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (NitikError $record) => $record->update(['is_resolved' => true]))
                        ->visible(fn (NitikError $record): bool => ! $record->is_resolved),
                    DeleteAction::make()->label('删除'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('操作'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('markResolvedBulk')
                        ->label('标记已解决')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_resolved' => true])),
                    DeleteBulkAction::make()->label('删除所选'),
                ]),
            ])
            ->recordUrl(fn (NitikError $record): string => Pages\ViewNitikError::getUrl(['record' => $record]));
    }

    public static function getNavigationBadge(): ?string
    {
        if (! (auth()->user()?->isSuperAdmin() ?? false)) {
            return null;
        }

        return static::getModel()::where('is_resolved', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('is_resolved', false)->exists() ? 'danger' : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNitikErrors::route('/'),
            'view' => Pages\ViewNitikError::route('/{record}'),
        ];
    }

    private static function levelColor(string $level): string
    {
        return match (strtolower($level)) {
            'error', 'critical', 'emergency' => 'danger',
            'warning' => 'warning',
            default => 'gray',
        };
    }
}
