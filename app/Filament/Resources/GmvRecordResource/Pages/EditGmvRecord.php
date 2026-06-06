<?php

namespace App\Filament\Resources\GmvRecordResource\Pages;

use App\Filament\Resources\GmvRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGmvRecord extends EditRecord
{
    protected static string $resource = GmvRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => GmvRecordResource::canDelete($this->record)),
        ];
    }
}
