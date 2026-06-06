<?php

namespace App\Filament\Resources\GmvRecordResource\Pages;

use App\Filament\Resources\GmvRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGmvRecords extends ListRecords
{
    protected static string $resource = GmvRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => GmvRecordResource::canCreate()),
        ];
    }
}
