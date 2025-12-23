<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Courier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'courier_code',

        'full_name',
        'national_id',
        'phone',
        'birth_date',

        'governorate_id',
        'area_id',
        'address',

        'vehicle_type',
        'driving_license_expiry',
        'vehicle_license_expiry',

        'emergency_name',
        'emergency_relation',
        'emergency_phone_1',
        'emergency_phone_2',
        'emergency_address',

        'is_active',

        // legacy commission columns (كما هي)
        'delivery_fee',
        'delivery_percent',
        'paid_fee',
        'paid_percent',
        'return_fee',
        'return_percent',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'driving_license_expiry' => 'date',
        'vehicle_license_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    /* ================= Relations ================= */

    public function user()
    {
        return $this->hasOne(User::class, 'courier_id');
    }

    public function commission()
    {
        return $this->hasOne(CourierCommission::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /* ================= Boot ================= */

    protected static function booted()
    {
        static::creating(function ($courier) {
            if (! $courier->courier_code) {
                $lastId = self::max('id') ?? 0;
                $courier->courier_code = 'CR-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
