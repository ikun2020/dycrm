<?php

namespace App\Filament\Resources\SampleResource\Pages;

use App\Filament\Resources\SampleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSample extends EditRecord
{
    protected static string $resource = SampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => SampleResource::canDelete($this->record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = SampleResource::prepareShipmentData($data);
        $data['owner_id'] = $this->record->owner_id;

        return $data;
    }
}
