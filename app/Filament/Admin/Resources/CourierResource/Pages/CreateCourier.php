<?php

namespace App\Filament\Admin\Resources\CourierResource\Pages;

use App\Filament\Admin\Resources\CourierResource;
use App\Models\Area;
use App\Models\Courier;
use App\Models\CourierCommission;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;

    protected function handleRecordCreation(array $data): Courier
    {
        // email/password مش dehydrated (جاية من $this->data)
        $email = $this->data['email'] ?? null;
        $password = $this->data['password'] ?? null;

        if (! $email || ! $password) {
            throw ValidationException::withMessages([
                'email' => 'البريد الإلكتروني مطلوب.',
                'password' => 'كلمة المرور مطلوبة.',
            ]);
        }

        // منع تعارض الإيميل على users
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            ]);
        }

        // commissions
        $commissionData = $data['commission'] ?? [];
        unset($data['commission']);

        // city (NOT NULL) → خليها اسم المنطقة لو موجودة
        if (empty($data['city']) && ! empty($data['area_id'])) {
            $data['city'] = Area::whereKey($data['area_id'])->value('name') ?? '';
        }

        // إنشاء courier
        $courier = Courier::create($data);

        // حفظ commissions (حوّل null → 0)
        $commissionPayload = [
            'delivery_value'            => (float) ($commissionData['delivery_value'] ?? 0),
            'delivery_percentage'       => (float) ($commissionData['delivery_percentage'] ?? 0),
            'paid_value'                => (float) ($commissionData['paid_value'] ?? 0),
            'paid_percentage'           => (float) ($commissionData['paid_percentage'] ?? 0),
            'sender_return_value'       => (float) ($commissionData['sender_return_value'] ?? 0),
            'sender_return_percentage'  => (float) ($commissionData['sender_return_percentage'] ?? 0),
        ];

        CourierCommission::create([
            'courier_id' => $courier->id,
            ...$commissionPayload,
        ]);

        // Sync legacy columns داخل couriers (اختياري لكن عندك موجود في DB)
        $courier->update([
            'delivery_fee'     => $commissionPayload['delivery_value'],
            'delivery_percent' => $commissionPayload['delivery_percentage'],
            'paid_fee'         => $commissionPayload['paid_value'],
            'paid_percent'     => $commissionPayload['paid_percentage'],
            'return_fee'       => $commissionPayload['sender_return_value'],
            'return_percent'   => $commissionPayload['sender_return_percentage'],
        ]);

        // إنشاء user + ربطه
        User::create([
            'name'       => $courier->full_name,
            'email'      => $email,
            'password'   => Hash::make($password),
            'courier_id' => $courier->id,
            'branch_id'  => $courier->branch_id, // موجود في DB
        ]);

        return $courier;
    }
}
