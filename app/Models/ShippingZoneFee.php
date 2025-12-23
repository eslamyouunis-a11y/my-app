<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZoneFee extends Model
{
    protected $fillable = [
        'shipping_fee_id',
        'area_id',
        'home_price',
        'home_sla_days',
        'is_active',
    ];

    public function shippingFee(): BelongsTo
    {
        return $this->belongsTo(ShippingFee::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
