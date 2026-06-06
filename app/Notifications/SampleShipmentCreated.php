<?php

namespace App\Notifications;

use App\Models\Sample;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as LaravelNotification;

class SampleShipmentCreated extends LaravelNotification
{
    use Queueable;

    public function __construct(
        private readonly Sample $sample,
        private readonly ?string $actorName = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->sample->loadMissing(['creator', 'sampleItem']);

        $creatorName = $this->sample->creator?->nickname ?: __('Unselected Creator');
        $sampleName = $this->sample->sampleItem?->name ?: $this->sample->sample_name ?: __('Unselected Sample');

        return FilamentNotification::make()
            ->title(__('New Sample Shipment Reminder'))
            ->body(__(':actor created a sample shipment: :creator / :sample x :quantity', [
                'actor' => $this->actorName ?: __('Staff'),
                'creator' => $creatorName,
                'sample' => $sampleName,
                'quantity' => $this->sample->quantity ?: 1,
            ]))
            ->icon('heroicon-o-truck')
            ->iconColor('primary')
            ->actions([
                Action::make('view')
                    ->label(__('View Sample Shipment'))
                    ->url(url('/admin/samples/'.$this->sample->id.'/edit')),
            ])
            ->getDatabaseMessage();
    }
}
