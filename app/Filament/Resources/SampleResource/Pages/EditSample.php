<?php

namespace App\Filament\Resources\SampleResource\Pages;

use App\Filament\Resources\SampleResource;
use App\Notifications\SampleShipmentCreated;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Notifications\DatabaseNotification;

class EditSample extends EditRecord
{
    protected static string $resource = SampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => SampleResource::canDelete($this->record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = SampleResource::prepareShipmentData($data);
        $data['owner_id'] = $this->record->owner_id;

        return $data;
    }

    protected function afterFill(): void
    {
        $this->markRelatedSampleShipmentNotificationsAsRead();
    }

    protected function afterSave(): void
    {
        $this->dispatch('dycrm-sample-shipment-badge-refresh');
    }

    private function markRelatedSampleShipmentNotificationsAsRead(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $sampleId = (string) $this->record->getKey();
        $legacyEditPath = '/admin/samples/'.$sampleId.'/edit';

        $user->unreadNotifications()
            ->where('type', SampleShipmentCreated::class)
            ->get()
            ->filter(fn (DatabaseNotification $notification): bool => (string) data_get($notification->data, 'sample_id') === $sampleId
                || str_contains(json_encode(data_get($notification->data, 'actions', []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '', $legacyEditPath))
            ->each
            ->markAsRead();
    }
}
