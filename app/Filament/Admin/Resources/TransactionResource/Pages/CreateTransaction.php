<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // بنستخدم Transaction عشان نضمن ان الفلوس تتخصم وتتسجل في نفس اللحظة
        return DB::transaction(function () use ($data) {

            // 1. إنشاء سجل الورقة المالية
            $transaction = static::getModel()::create($data);

            // 2. تحديث رصيد المستفيد الحقيقي
            $modelClass = $data['transactable_type']; // Branch, Merchant, Courier
            $modelId = $data['transactable_id'];
            $walletColumn = $data['wallet_type']; // العمود في الداتابيز
            $amount = $data['amount'];
            $type = $data['type']; // credit (+) or debit (-)

            // نجيب المستفيد
            $entity = $modelClass::findOrFail($modelId);

            // نطبق العملية الحسابية
            if ($type === 'credit') {
                $entity->increment($walletColumn, $amount);
            } else {
                $entity->decrement($walletColumn, $amount);
            }

            return $transaction;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
