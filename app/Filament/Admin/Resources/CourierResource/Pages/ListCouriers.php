<?php

namespace App\Filament\Admin\Resources\CourierResource\Pages;

use App\Filament\Admin\Resources\CourierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCouriers extends ListRecords
{
    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
