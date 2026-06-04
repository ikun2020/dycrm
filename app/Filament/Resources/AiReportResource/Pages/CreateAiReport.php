<?php

namespace App\Filament\Resources\AiReportResource\Pages;

use App\Filament\Resources\AiReportResource;
use App\Models\Creator;
use App\Models\Product;
use App\Services\CreatorAiScoringService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateAiReport extends CreateRecord
{
    protected static string $resource = AiReportResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Generate AI Report'))
                ->description(__('Select a creator and product, then the system will generate an AI diagnosis report and update the creator score.'))
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('creator_id')
                        ->label(__('Creator'))
                        ->options(fn () => Creator::query()->orderByDesc('id')->pluck('nickname', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('product_id')
                        ->label(__('Product'))
                        ->options(fn () => Product::query()
                            ->where('status', 'active')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Product $product): array => [
                                $product->id => collect([$product->name, $product->brand, $product->category])
                                    ->filter()
                                    ->implode(' / '),
                            ]))
                        ->searchable()
                        ->required(),
                ]),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreatorAiScoringService::class)->score(
                Creator::query()->findOrFail($data['creator_id']),
                auth()->user(),
                (int) $data['product_id'],
            );
        } catch (Throwable $exception) {
            Notification::make()
                ->title(__('AI Report Failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw $exception;
        }
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('Generate AI Report'));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('AI Report Generated');
    }
}
