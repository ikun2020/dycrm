<?php

namespace App\Filament\Resources\CreatorResource\RelationManagers;

use App\Filament\Resources\CreatorResource;
use App\Filament\Resources\FollowUpResource;
use App\Models\Creator;
use App\Models\FollowUp;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    protected static ?string $title = 'Follow-up Timeline';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Follow-up Timeline');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('channel')
                ->label(__('Channel'))
                ->options(FollowUpResource::channelOptions())
                ->default('wechat')
                ->required(),
            Forms\Components\Select::make('user_id')
                ->label(__('User'))
                ->relationship('user', 'name')
                ->default(auth()->id())
                ->searchable()
                ->preload(),
            Forms\Components\DateTimePicker::make('contacted_at')
                ->label(__('Contacted At'))
                ->default(now())
                ->seconds(false)
                ->required(),
            Forms\Components\DateTimePicker::make('next_follow_up_at')
                ->label(__('Next Follow-up At'))
                ->seconds(false),
            Forms\Components\Select::make('status_after')
                ->label(__('Status After'))
                ->options(CreatorResource::statusOptions()),
            Forms\Components\TextInput::make('contact_person')
                ->label(__('Contact Person'))
                ->maxLength(255),
            Forms\Components\Textarea::make('content')
                ->label(__('Content'))
                ->rows(4)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('next_action')
                ->label(__('Next Action'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Follow-up Timeline'))
            ->defaultSort('contacted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('contacted_at')
                    ->label(__('Contacted At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('channel')
                    ->label(__('Channel'))
                    ->formatStateUsing(fn (string $state): string => FollowUpResource::channelOptions()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('status_after')
                    ->label(__('Status After'))
                    ->formatStateUsing(fn (?string $state): string => $state ? (CreatorResource::statusOptions()[$state] ?? $state) : '-')
                    ->badge(),
                Tables\Columns\TextColumn::make('content')
                    ->label(__('Content'))
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('next_action')
                    ->label(__('Next Action'))
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('next_follow_up_at')
                    ->label(__('Next Follow-up'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add Follow-up'))
                    ->visible(fn (): bool => FollowUpResource::canCreate())
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] ??= auth()->id();

                        return $data;
                    })
                    ->after(function (FollowUp $record): void {
                        $this->syncCreatorFromFollowUp($record);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (FollowUp $record): bool => FollowUpResource::canEdit($record))
                    ->after(function (FollowUp $record): void {
                        $this->syncCreatorFromFollowUp($record);
                    }),
                DeleteAction::make()
                    ->visible(fn (FollowUp $record): bool => FollowUpResource::canDelete($record)),
            ]);
    }

    private function syncCreatorFromFollowUp(FollowUp $followUp): void
    {
        /** @var Creator $creator */
        $creator = $this->getOwnerRecord();

        $creator->forceFill([
            'last_contacted_at' => $followUp->contacted_at,
            'next_follow_up_at' => $followUp->next_follow_up_at,
            'cooperation_status' => $followUp->status_after ?: $creator->cooperation_status,
            'owner_id' => $creator->owner_id ?: ($followUp->user_id ?: auth()->id()),
        ])->save();
    }
}
