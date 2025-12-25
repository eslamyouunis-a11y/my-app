<?php

namespace App\Filament\Admin\Resources\MerchantResource\Pages;

use App\Filament\Admin\Resources\MerchantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchant extends ViewRecord
{
    protected static string $resource = MerchantResource::class;
    protected static string $view = 'filament.admin.merchants.view-merchant';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('تعديل التاجر'),
        ];
    }
}
