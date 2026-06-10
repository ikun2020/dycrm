<?php

namespace App\Filament\Resources\KnowledgePostResource\RelationManagers;

use App\Models\KnowledgeComment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Comments');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->isSuperAdmin()
            || (auth()->user()?->can('ViewAny:KnowledgeComment') ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('user_id')
                ->label(__('User'))
                ->relationship('user', 'name')
                ->default(auth()->id())
                ->disabled()
                ->dehydrated(false)
                ->helperText(__('The owner is fixed to the current user.'))
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options(self::statusOptions())
                ->default('visible')
                ->selectablePlaceholder(false)
                ->required(),
            Forms\Components\Textarea::make('content')
                ->label(__('Comment Content'))
                ->rows(4)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Comments'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->label(__('Comment Content'))
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hidden' => 'gray',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add Comment'))
                    ->visible(fn (): bool => self::canCreateComment())
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (KnowledgeComment $record): bool => self::canEditComment()),
                DeleteAction::make()
                    ->visible(fn (KnowledgeComment $record): bool => self::canDeleteComment($record)),
            ]);
    }

    public static function canCreateComment(): bool
    {
        return auth()->user()?->isSuperAdmin()
            || (auth()->user()?->can('Create:KnowledgeComment') ?? false);
    }

    public static function canEditComment(): bool
    {
        return auth()->user()?->isSuperAdmin()
            || (auth()->user()?->can('Update:KnowledgeComment') ?? false);
    }

    public static function canDeleteComment(KnowledgeComment $comment): bool
    {
        return auth()->user()?->isSuperAdmin()
            || (auth()->user()?->can('Delete:KnowledgeComment') ?? false);
    }

    public static function statusOptions(): array
    {
        return [
            'visible' => __('Visible'),
            'hidden' => __('Hidden'),
        ];
    }
}
