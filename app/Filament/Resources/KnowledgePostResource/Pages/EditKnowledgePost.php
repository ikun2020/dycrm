<?php

namespace App\Filament\Resources\KnowledgePostResource\Pages;

use App\Filament\Resources\KnowledgePostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgePost extends EditRecord
{
    protected static string $resource = KnowledgePostResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    public static function getNavigationLabel(): string
    {
        return __('Edit');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === 'published' && ! KnowledgePostResource::canPublishAny()) {
            $data['status'] = $this->record->status;
            $data['published_at'] = $this->record->published_at;
        }

        return $data;
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
            Actions\DeleteAction::make()
                ->visible(fn (): bool => KnowledgePostResource::canDelete($this->record)),
        ];
    }
}
