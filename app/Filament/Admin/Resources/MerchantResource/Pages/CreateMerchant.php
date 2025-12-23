<?php

namespace App\Filament\Admin\Resources\MerchantResource\Pages;

use App\Filament\Admin\Resources\MerchantResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateMerchant extends CreateRecord
{
    protected static string $resource = MerchantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // بيانات اليوزر
            $userData = [
                'name'     => $data['contact_person_name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ];

            // تنظيف الداتا للتاجر
            unset($data['email']);
            unset($data['password']);

            // إنشاء التاجر
            $merchant = static::getModel()::create($data);

            // إنشاء اليوزر وربطه بالتاجر
            $user = User::create([
                'name'        => $userData['name'],
                'email'       => $userData['email'],
                'password'    => Hash::make($userData['password']),
                'merchant_id' => $merchant->id,
            ]);

            // إعطاء الصلاحية
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('merchant');
            }

            return $merchant;
        });
    }
}
