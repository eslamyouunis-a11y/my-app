<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany; // ✅

class Merchant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'merchant_code', 'name', 'email', 'address',
        'governorate_id', 'area_id', 'branch_id',
        'contact_person_name', 'contact_person_phone',
        'follow_up_name', 'follow_up_phone',
        'extra_weight_price',
        'paid_return_fee', 'paid_return_percent',
        'return_on_sender_fee', 'return_on_sender_percent',
        'cancellation_fee', 'cancellation_percent',
        'is_active',
        // ✅ الأرصدة (لو ضفتها في المايجريشن)
        // 'balance',
    ];

    protected static function booted()
    {
        static::creating(function ($merchant) {
            $merchant->merchant_code = 'M-' . time();
        });

        static::deleted(function ($merchant) {
            $merchant->user()->delete();
        });
    }

    // العلاقات الأساسية
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function governorate(): BelongsTo { return $this->belongsTo(Governorate::class); }
    public function area(): BelongsTo { return $this->belongsTo(Area::class); }
    public function user(): HasOne { return $this->hasOne(User::class); }
    public function specialPrices(): HasMany { return $this->hasMany(MerchantSpecialPrice::class); }
    public function products(): HasMany { return $this->hasMany(MerchantProduct::class); }

    // ✅ علاقة الأوراق المالية (مهمة جداً للخصم والإضافة)
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }
}
