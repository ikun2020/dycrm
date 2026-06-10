<?php

namespace App\Filament\Resources\KnowledgePostResource\Pages;

use App\Filament\Resources\KnowledgePostResource;
use App\Filament\Resources\KnowledgePostResource\RelationManagers\CommentsRelationManager;
use App\Models\KnowledgeComment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ManageKnowledgePostComments extends ManageRelatedRecords
{
    protected static string $resource = KnowledgePostResource::class;

    protected static string $relationship = 'comments';

    protected static ?string $relationshipTitle = 'Comments';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function getNavigationLabel(): string
    {
        return __('Comments');
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
                ->options(CommentsRelationManager::statusOptions())
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
                    ->formatStateUsing(fn (string $state): string => CommentsRelationManager::statusOptions()[$state] ?? $state)
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
                    ->visible(fn (): bool => CommentsRelationManager::canCreateComment())
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (KnowledgeComment $record): bool => CommentsRelationManager::canEditComment()),
                DeleteAction::make()
                    ->visible(fn (KnowledgeComment $record): bool => CommentsRelationManager::canDeleteComment($record)),
            ]);
    }
}
