<?php

namespace App\Filament\Resources\SampleItemResource\Pages;

use App\Filament\Resources\SampleItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSampleItems extends ListRecords
{
    protected static string $resource = SampleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => SampleItemResource::canCreate()),
        ];
    }
}
