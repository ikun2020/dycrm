<?php

namespace App\Filament\Resources\LiveSessionResource\Pages;

use App\Filament\Resources\LiveSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLiveSession extends EditRecord
{
    protected static string $resource = LiveSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
