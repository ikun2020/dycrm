<?php

namespace App\Filament\Resources\AiReportResource\Pages;

use App\Filament\Resources\AiReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiReports extends ListRecords
{
    protected static string $resource = AiReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
