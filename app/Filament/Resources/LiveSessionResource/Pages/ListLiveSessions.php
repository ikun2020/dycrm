<?php

namespace App\Filament\Resources\LiveSessionResource\Pages;

use App\Filament\Resources\LiveSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiveSessions extends ListRecords
{
    protected static string $resource = LiveSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
