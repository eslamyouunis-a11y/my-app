<?php

namespace App\Filament\Admin\Resources\CourierResource\Pages;

use App\Filament\Admin\Resources\CourierResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCourier extends ViewRecord
{
    protected static string $resource = CourierResource::class;

    protected static string $view = 'filament.admin.couriers.view-courier';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}
