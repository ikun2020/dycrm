<?php

namespace App\Filament\Resources\SampleItemResource\Pages;

use App\Filament\Resources\SampleItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSampleItem extends EditRecord
{
    protected static string $resource = SampleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => SampleItemResource::canDelete($this->record)),
        ];
    }
}
