<?php

namespace App\Filament\Resources\SampleResource\Pages;

use App\Filament\Resources\SampleResource;
use App\Models\User;
use App\Notifications\SampleShipmentCreated;
use Filament\Resources\Pages\CreateRecord;

class CreateSample extends CreateRecord
{
    protected static string $resource = SampleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = SampleResource::prepareShipmentData($data);
        $data['owner_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        User::query()
            ->where('is_active', true)
            ->whereKeyNot(auth()->id())
            ->whereHas('permissionGroup', fn ($query) => $query
                ->where('is_active', true)
                ->where('notify_sample_shipments', true))
            ->get()
            ->each(fn (User $user) => $user->notify(
                new SampleShipmentCreated($this->record, auth()->user()?->name),
            ));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('Sample shipment created');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
