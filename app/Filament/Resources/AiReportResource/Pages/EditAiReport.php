<?php

namespace App\Filament\Resources\AiReportResource\Pages;

use App\Filament\Resources\AiReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAiReport extends EditRecord
{
    protected static string $resource = AiReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => AiReportResource::canDelete($this->record)),
        ];
    }
}
