<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperationLogResource\Pages;
use App\Models\OperationLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OperationLogResource extends Resource
{
    protected static ?string $model = OperationLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $modelLabel = '操作日志';

    protected static ?string $pluralModelLabel = '操作日志';

    public static function canViewAny(): bool
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

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->dateTime('Y-m-d H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label(__('Operator'))->placeholder('-'),
                Tables\Columns\TextColumn::make('action')
                    ->label(__('Operation'))
                    ->badge()
                    ->color(fn (?string $state): string => self::actionColor($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_label')
                    ->label(__('Operation Subject'))
                    ->formatStateUsing(fn (?string $state, OperationLog $record): string => self::subjectLabel($state, $record))
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? (string) $state : '-')
                    ->wrap()
                    ->limit(80),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label(__('Operation'))
                    ->options(fn (): array => OperationLog::query()
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => self::canDeleteAny()),
                ]),
            ]);
    }

    private static function actionColor(?string $action): string
    {
        return match ($action) {
            'creator.deleted', 'ai_report.deleted', 'sample.deleted' => 'danger',
            'creators.imported' => 'success',
            default => 'gray',
        };
    }

    private static function subjectLabel(?string $subject, OperationLog $record): string
    {
        if ($record->action === 'sample.deleted') {
            $parts = array_filter([
                $record->properties['creator'] ?? null,
                $record->properties['sample'] ?? null,
            ]);

            return $parts === [] ? ($subject ?: '-') : implode(' / ', $parts);
        }

        if ($record->action === 'ai_report.deleted') {
            return filled($record->properties['creator'] ?? null)
                ? (string) $record->properties['creator']
                : (filled($subject) ? (string) $subject : '-');
        }

        return filled($subject) ? (string) $subject : '-';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperationLogs::route('/'),
        ];
    }
}
