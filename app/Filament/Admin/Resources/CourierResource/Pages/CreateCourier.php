<?php

namespace App\Filament\Admin\Resources\CourierResource\Pages;

use App\Filament\Admin\Resources\CourierResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            // 1. استخراج بيانات المستخدم
            $userData = [
                'name'     => $data['full_name'], // نستخدم اسم المندوب كاسم للمستخدم
                'email'    => $data['email'],
                'password' => $data['password'],
            ];

            // 2. تنظيف البيانات (حذف الحقول التي لا تخص جدول couriers)
            unset($data['email']);
            unset($data['password']);

            // 3. توليد كود المندوب تلقائياً (مثال بسيط)
            $data['courier_code'] = 'CR-' . time(); // يمكنك تحسينها لاحقاً

            // 4. إنشاء المندوب
            $courier = static::getModel()::create($data);

            // 5. إنشاء المستخدم وربطه بالمندوب
            $user = User::create([
                'name'       => $userData['name'],
                'email'      => $userData['email'],
                'password'   => Hash::make($userData['password']),
                'courier_id' => $courier->id,
                // 'branch_id' => $courier->branch_id, // اختياري: لو عايز تربط اليوزر بالفرع كمان
            ]);

            // 6. تعيين الصلاحية
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('courier');
            }

            return $courier;
        });
    }
}
