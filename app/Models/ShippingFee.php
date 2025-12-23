<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingFee extends Model
{
    protected $fillable = [
        'from_governorate_id',
        'to_governorate_id',
        'home_price',
        'home_sla_days',
        'office_price',
        'office_sla_days',
        'is_active',
    ];

    public function fromGovernorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'from_governorate_id');
    }

    public function toGovernorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'to_governorate_id');
    }

    public function zoneFees(): HasMany
    {
        return $this->hasMany(ShippingZoneFee::class);
    }
}
