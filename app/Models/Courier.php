<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany; // ✅
use Illuminate\Database\Eloquent\SoftDeletes;

class Courier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'courier_code',
        'full_name',
        'phone',
        'national_id',
        'birth_date',
        'address',
        'vehicle_type',
        'driving_license_expiry',
        'vehicle_license_expiry',
        'branch_id',
        'governorate_id',
        'area_id',
        'is_active',
        // ✅ الأرصدة (لو ضفتها في المايجريشن)
        // 'commission_balance', 'custody_balance',
    ];

    // العلاقات الأساسية
    public function user(): HasOne { return $this->hasOne(User::class, 'courier_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function governorate(): BelongsTo { return $this->belongsTo(Governorate::class); }
    public function area(): BelongsTo { return $this->belongsTo(Area::class); }
    public function commission(): HasOne { return $this->hasOne(CourierCommission::class); }

    // ✅ علاقة الأوراق المالية (مهمة جداً للخصم والإضافة)
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }
}
