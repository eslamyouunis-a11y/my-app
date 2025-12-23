<?php

namespace App\Filament\Admin\Resources\CourierResource\Pages;

use App\Filament\Admin\Resources\CourierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourier extends ViewRecord
{
    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('تعديل المندوب'),
        ];
    }
}
