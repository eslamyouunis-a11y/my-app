<?php

namespace App\Filament\Admin\Resources\CourierResource\Pages;

use App\Filament\Admin\Resources\CourierResource;
use App\Models\Area;
use App\Models\CourierCommission;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditCourier extends EditRecord
{
    protected static string $resource = CourierResource::class;

    /**
     * ✅ خلي صفحة التعديل Full Width
     * لازم تكون public + non-static + نفس return type المتوقع
     */
    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /**
         * =========================
         * 1) commissions
         * =========================
         */
        $commissionData = $data['commission'] ?? [];
        unset($data['commission']);

        /**
         * =========================
         * 2) Legacy city (لو موجودة NOT NULL)
         * - نخليها اسم المنطقة لو area_id موجودة
         * =========================
         */
        if ((empty($data['city']) || ! array_key_exists('city', $data)) && ! empty($data['area_id'])) {
            $data['city'] = Area::whereKey($data['area_id'])->value('name') ?? $record->city;
        }

        /**
         * =========================
         * 3) تحديث courier
         * =========================
         */
        $record->update($data);

        /**
         * =========================
         * 4) UpdateOrCreate commissions (null -> 0)
         * =========================
         */
        $commissionPayload = [
            'delivery_value'           => (float) ($commissionData['delivery_value'] ?? 0),
            'delivery_percentage'      => (float) ($commissionData['delivery_percentage'] ?? 0),
            'paid_value'               => (float) ($commissionData['paid_value'] ?? 0),
            'paid_percentage'          => (float) ($commissionData['paid_percentage'] ?? 0),
            'sender_return_value'      => (float) ($commissionData['sender_return_value'] ?? 0),
            'sender_return_percentage' => (float) ($commissionData['sender_return_percentage'] ?? 0),
        ];

        $commission = CourierCommission::updateOrCreate(
            ['courier_id' => $record->id],
            $commissionPayload
        );

        /**
         * =========================
         * 5) Sync legacy columns داخل couriers (لو عندك أعمدة قديمة)
         * =========================
         */
        $record->update([
            'delivery_fee'     => $commission->delivery_value ?? 0,
            'delivery_percent' => $commission->delivery_percentage ?? 0,
            'paid_fee'         => $commission->paid_value ?? 0,
            'paid_percent'     => $commission->paid_percentage ?? 0,
            'return_fee'       => $commission->sender_return_value ?? 0,
            'return_percent'   => $commission->sender_return_percentage ?? 0,
        ]);

        /**
         * =========================
         * 6) User Sync
         * - الإيميل عرض فقط (مش بنغيره)
         * - الباسورد فقط اختياري
         * =========================
         */
        $password = $this->data['password'] ?? null; // لأنه dehydrated(false) في الفورم

        if ($record->user) {
            // مزامنة branch_id لو موجود عندك في users
            if (array_key_exists('branch_id', $record->user->getAttributes())) {
                $record->user->branch_id = $record->branch_id;
            }

            if (! empty($password)) {
                $record->user->password = Hash::make($password);
            }

            $record->user->save();
        }

        return $record;
    }
}
