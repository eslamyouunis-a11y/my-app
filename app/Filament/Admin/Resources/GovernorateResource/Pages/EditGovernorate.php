<?php

namespace App\Filament\Admin\Resources\GovernorateResource\Pages;

use App\Filament\Admin\Resources\GovernorateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGovernorate extends EditRecord
{
    protected static string $resource = GovernorateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
