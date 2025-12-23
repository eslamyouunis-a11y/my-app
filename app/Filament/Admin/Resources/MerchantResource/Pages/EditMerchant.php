<?php

namespace App\Filament\Admin\Resources\MerchantResource\Pages;

use App\Filament\Admin\Resources\MerchantResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditMerchant extends EditRecord
{
    protected static string $resource = MerchantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user) {
            $data['email'] = $this->record->user->email;
        }
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // تحديث اليوزر
            if ($record->user) {
                $userUpdate = [
                    'name'  => $data['contact_person_name'],
                    'email' => $data['email'],
                ];
                if (! empty($data['password'])) {
                    $userUpdate['password'] = Hash::make($data['password']);
                }
                $record->user->update($userUpdate);
            }

            // تنظيف الداتا للتاجر
            unset($data['email']);
            unset($data['password']);

            // تحديث التاجر
            $record->update($data);

            return $record;
        });
    }
}
