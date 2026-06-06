<?php

namespace App\Filament\Resources\AiSettingResource\Pages;

use App\Filament\Resources\AiSettingResource;
use App\Models\AiSetting;
use Filament\Resources\Pages\CreateRecord;

class CreateAiSetting extends CreateRecord
{
    protected static string $resource = AiSettingResource::class;

    public function mount(): void
    {
        if ($setting = AiSetting::query()->first()) {
            $this->redirect(AiSettingResource::getUrl('edit', ['record' => $setting]));

            return;
        }

        parent::mount();
    }
}
