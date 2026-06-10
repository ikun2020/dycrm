<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ChecksMenuPermission;
use App\Filament\Resources\KnowledgePostResource\Pages;
use App\Filament\Resources\KnowledgePostResource\RelationManagers\CommentsRelationManager;
use App\Models\KnowledgePost;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;

class KnowledgePostResource extends Resource
{
    use ChecksMenuPermission;

    protected static ?string $model = KnowledgePost::class;

    protected static ?bool $hasKnowledgePostCategoryColumn = null;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $menuPermissionKey = 'knowledge-posts';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = "\u{77E5}\u{8BC6}\u{5E93}";

    protected static ?string $modelLabel = "\u{77E5}\u{8BC6}\u{6587}\u{7AE0}";

    protected static ?string $pluralModelLabel = "\u{77E5}\u{8BC6}\u{5E93}";

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Knowledge Content'))
                ->description(__('Create internal knowledge posts for creator operations, playbooks, and review notes.'))
                ->icon('heroicon-o-document-text')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\TextInput::make('title')
                        ->label(__('Title'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('category')
                        ->label(__('Article Category'))
                        ->maxLength(100)
                        ->visible(fn (): bool => self::hasKnowledgePostCategoryColumn())
                        ->dehydrated(fn (): bool => self::hasKnowledgePostCategoryColumn()),
                    Forms\Components\Select::make('status')
                        ->label(__('Status'))
                        ->options(fn (?KnowledgePost $record): array => self::statusOptionsForForm($record))
                        ->disabled(fn (?KnowledgePost $record): bool => $record?->status === 'published' && ! self::canPublishAny())
                        ->default('draft')
                        ->required(),
                    Forms\Components\Toggle::make('is_featured')
                        ->label(__('Featured')),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label(__('Published At'))
                        ->seconds(false),
                    Forms\Components\Select::make('user_id')
                        ->label(__('Author'))
                        ->relationship('author', 'name')
                        ->default(auth()->id())
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('excerpt')
                        ->label(__('Excerpt'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('content')
                        ->label(__('Post Content'))
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('knowledge-posts')
                        ->fileAttachmentsVisibility('public')
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->components([
                Section::make(__('Post Details'))
                    ->columnSpan(['lg' => 1])
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('Title'))
                            ->columnSpanFull(),
                        TextEntry::make('category')
                            ->label(__('Article Category'))
                            ->columnSpanFull()
                            ->placeholder('-')
                            ->visible(fn (): bool => self::hasKnowledgePostCategoryColumn()),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'archived' => 'gray',
                                default => 'warning',
                            }),
                        IconEntry::make('is_featured')
                            ->label(__('Featured'))
                            ->boolean(),
                        TextEntry::make('author.name')
                            ->label(__('Author')),
                        TextEntry::make('published_at')
                            ->label(__('Published At'))
                            ->dateTime('Y-m-d H:i'),
                        TextEntry::make('excerpt')
                            ->label(__('Excerpt'))
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
                Section::make(__('Post Content'))
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextEntry::make('content')
                            ->label('')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Comments'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('comments')
                            ->hiddenLabel()
                            ->placeholder(__('No comments yet.'))
                            ->state(fn (KnowledgePost $record) => $record->comments()
                                ->with('user')
                                ->latest()
                                ->get())
                            ->schema([
                                TextEntry::make('content')
                                    ->label(__('Comment Content'))
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                                TextEntry::make('status')
                                    ->label(__('Status'))
                                    ->formatStateUsing(fn (string $state): string => CommentsRelationManager::statusOptions()[$state] ?? $state)
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'hidden' => 'gray',
                                        default => 'success',
                                    }),
                                TextEntry::make('user.name')
                                    ->label(__('User'))
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->label(__('Created At'))
                                    ->dateTime('Y-m-d H:i'),
                            ])
                            ->columns(['md' => 3])
                            ->contained(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable(self::knowledgePostSearchColumns())
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('Article Category'))
                    ->badge()
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable()
                    ->visible(fn (): bool => self::hasKnowledgePostCategoryColumn()),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'published' => 'heroicon-o-check-circle',
                        'archived' => 'heroicon-o-archive-box',
                        default => 'heroicon-o-document',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'archived' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('Featured'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->label(__('Comments'))
                    ->counts('comments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label(__('Author'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('Published At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('Article Category'))
                    ->options(fn (): array => self::knowledgePostCategoryOptions())
                    ->visible(fn (): bool => self::hasKnowledgePostCategoryColumn()),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('Featured')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (KnowledgePost $record): bool => self::canView($record)),
                Action::make('publish')
                    ->label(__('Publish'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (KnowledgePost $record): bool => $record->status !== 'published' && self::canPublish($record))
                    ->action(function (KnowledgePost $record): void {
                        $record->forceFill([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                        ])->save();

                        Notification::make()
                            ->title(__('Knowledge post published'))
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (KnowledgePost $record): bool => self::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publishSelected')
                        ->label(__('Publish Selected'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => self::canPublishAny())
                        ->action(function (Collection $records): void {
                            $records->each(function (KnowledgePost $record): void {
                                $record->forceFill([
                                    'status' => 'published',
                                    'published_at' => $record->published_at ?? now(),
                                ])->save();
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => self::canDeleteAny()),
                ]),
            ])
            ->recordUrl(fn (KnowledgePost $record): string => Pages\ViewKnowledgePost::getUrl(['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgePosts::route('/'),
            'create' => Pages\CreateKnowledgePost::route('/create'),
            'view' => Pages\ViewKnowledgePost::route('/{record}'),
            'edit' => Pages\EditKnowledgePost::route('/{record}/edit'),
            'comments' => Pages\ManageKnowledgePostComments::route('/{record}/comments'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewKnowledgePost::class,
            Pages\EditKnowledgePost::class,
            Pages\ManageKnowledgePostComments::class,
        ]);
    }

    public static function canPublish(KnowledgePost $record): bool
    {
        return auth()->user()?->isSuperAdmin()
            || (auth()->user()?->can('Publish:KnowledgePost') ?? false);
    }

    public static function canPublishAny(): bool
    {
        return auth()->user()?->isSuperAdmin()
            || (auth()->user()?->can('Publish:KnowledgePost') ?? false);
    }

    public static function canView(Model $record): bool
    {
        return self::canViewAny();
    }

    public static function statusOptions(): array
    {
        return [
            'draft' => __('Draft'),
            'published' => __('Published'),
            'archived' => __('Archived'),
        ];
    }

    public static function statusOptionsForForm(?KnowledgePost $record = null): array
    {
        $options = self::statusOptions();

        if (self::canPublishAny() || $record?->status === 'published') {
            return $options;
        }

        unset($options['published']);

        return $options;
    }

    protected static function hasKnowledgePostCategoryColumn(): bool
    {
        if (self::$hasKnowledgePostCategoryColumn !== null) {
            return self::$hasKnowledgePostCategoryColumn;
        }

        try {
            return self::$hasKnowledgePostCategoryColumn = SchemaFacade::hasColumn('knowledge_posts', 'category');
        } catch (Throwable) {
            return self::$hasKnowledgePostCategoryColumn = false;
        }
    }

    protected static function knowledgePostSearchColumns(): array
    {
        $columns = ['title', 'slug', 'excerpt'];

        if (self::hasKnowledgePostCategoryColumn()) {
            $columns[] = 'category';
        }

        return $columns;
    }

    protected static function knowledgePostCategoryOptions(): array
    {
        if (! self::hasKnowledgePostCategoryColumn()) {
            return [];
        }

        return KnowledgePost::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->orderBy('category')
            ->pluck('category', 'category')
            ->all();
    }
}
