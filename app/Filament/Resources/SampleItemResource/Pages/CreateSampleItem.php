<?php

namespace App\Filament\Resources\SampleItemResource\Pages;

use App\Filament\Resources\SampleItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSampleItem extends CreateRecord
{
    protected static string $resource = SampleItemResource::class;
}
