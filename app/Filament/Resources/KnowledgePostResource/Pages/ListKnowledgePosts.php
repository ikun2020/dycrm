<?php

namespace App\Filament\Resources\KnowledgePostResource\Pages;

use App\Filament\Resources\KnowledgePostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgePosts extends ListRecords
{
    protected static string $resource = KnowledgePostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => KnowledgePostResource::canCreate()),
        ];
    }
}
