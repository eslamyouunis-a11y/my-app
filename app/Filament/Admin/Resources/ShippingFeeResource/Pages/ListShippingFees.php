<?php

namespace App\Filament\Admin\Resources\ShippingFeeResource\Pages;

use App\Filament\Admin\Resources\ShippingFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingFees extends ListRecords
{
    protected static string $resource = ShippingFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
