<?php

namespace App\Filament\Resources\KnowledgePostResource\Pages;

use App\Filament\Resources\KnowledgePostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgePost extends ViewRecord
{
    protected static string $resource = KnowledgePostResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-eye';

    public static function getNavigationLabel(): string
    {
        return __('View');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publish')
                ->label(__('Publish'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== 'published' && KnowledgePostResource::canPublish($this->record))
                ->action(function (): void {
                    $this->record->forceFill([
                        'status' => 'published',
                        'published_at' => $this->record->published_at ?? now(),
                    ])->save();
                }),
            Actions\Action::make('unpublish')
                ->label(__('Unpublish'))
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'published' && KnowledgePostResource::canPublish($this->record))
                ->action(function (): void {
                    $this->record->forceFill([
                        'status' => 'draft',
                    ])->save();
                }),
            Actions\EditAction::make()
                ->visible(fn (): bool => KnowledgePostResource::canEdit($this->record)),
        ];
    }
}
