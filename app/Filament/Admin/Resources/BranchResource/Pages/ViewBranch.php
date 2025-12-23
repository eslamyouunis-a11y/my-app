<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;

    // بنضيف زرار "تعديل" فوق عشان لو حب يغير بيانات وهو بيتفرج
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('تعديل البيانات'),
        ];
    }
}
