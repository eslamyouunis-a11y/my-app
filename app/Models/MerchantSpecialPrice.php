<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantSpecialPrice extends Model
{
    protected $fillable = [
        'merchant_id',
        'governorate_id',
        'area_id',

        // ✅ الحقول المتبقية فقط
        'delivery_fee',        // قيمة التوصيل للمنزل
        'office_delivery_fee'  // قيمة التوصيل للمكتب
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
