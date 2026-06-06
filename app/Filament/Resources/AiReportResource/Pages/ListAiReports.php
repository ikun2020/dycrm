<?php

namespace App\Filament\Resources\AiReportResource\Pages;

use App\Filament\Resources\AiReportResource;
use App\Models\Creator;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiReports extends ListRecords
{
    protected static string $resource = AiReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateAiReport')
                ->label(__('Generate AI Report'))
                ->icon('heroicon-o-sparkles')
                ->visible(fn (): bool => AiReportResource::canCreate())
                ->modalHeading(__('AI Rating'))
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalContent(fn () => view('filament.actions.creator-ai-diagnosis-modal', [
                    'creator' => null,
                    'creators' => Creator::query()
                        ->orderByDesc('id')
                        ->limit(5)
                        ->get(['id', 'nickname']),
                    'products' => Product::query()
                        ->where('status', 'active')
                        ->orderByDesc('id')
                        ->get(['id', 'name', 'brand', 'category']),
                ])),
        ];
    }
}
