<?php

namespace App\Filament\Resources\NitikErrorResource\Pages;

use App\Filament\Resources\NitikErrorResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Kholil\Nitik\Models\NitikError as NitikErrorModel;

class ListNitikErrors extends ListRecords
{
    protected static string $resource = NitikErrorResource::class;

    public function getDefaultActiveTab(): string|int|null
    {
        return 'unresolved';
    }

    public function getTabs(): array
    {
        $unresolvedCount = NitikErrorModel::query()
            ->where('is_resolved', false)
            ->count();

        return [
            'unresolved' => Tab::make('未解决')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', false))
                ->badge($unresolvedCount ?: null),
            'resolved' => Tab::make('已解决')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', true)),
            'all' => Tab::make('全部'),
        ];
    }
}
