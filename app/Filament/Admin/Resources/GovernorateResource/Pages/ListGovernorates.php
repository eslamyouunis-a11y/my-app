<?php

namespace App\Filament\Admin\Resources\GovernorateResource\Pages;

use App\Filament\Admin\Resources\GovernorateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGovernorates extends ListRecords
{
    protected static string $resource = GovernorateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
