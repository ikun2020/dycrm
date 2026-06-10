<?php

namespace App\Filament\Resources\KnowledgePostResource\Pages;

use App\Filament\Resources\KnowledgePostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgePost extends CreateRecord
{
    protected static string $resource = KnowledgePostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? null) === 'published' && ! KnowledgePostResource::canPublishAny()) {
            $data['status'] = 'draft';
            $data['published_at'] = null;
        }

        return $data;
    }
}
