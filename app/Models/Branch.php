<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany; // ✅
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_number',
        'name',
        'manager_name',
        'manager_phone',
        'email',
        'address',
        'governorate_id',
        'branch_type',
        'is_active',

        // العمولات
        'normal_delivery_fee', 'normal_delivery_percent',
        'normal_paid_return_fee', 'normal_paid_return_percent',
        'normal_return_on_sender_fee', 'normal_return_on_sender_percent',
        'office_delivery_fee', 'office_delivery_percent',

        // ✅ الأرصدة (لو ضفتها في المايجريشن)
        // 'commission_balance', 'total_balance', 'couriers_custody_balance',
    ];

    protected static function booted()
    {
        static::creating(function (Branch $branch) {
            if (! $branch->branch_number) {
                $branch->branch_number = DB::transaction(function () {
                    return (static::query()->lockForUpdate()->max('branch_number') ?? 0) + 1;
                });
            }
        });

        static::deleted(function (Branch $branch) {
            $branch->user()->delete();
        });

        static::restored(function (Branch $branch) {
            $branch->user()->restore();
        });
    }

    // العلاقات الأساسية
    public function governorate(): BelongsTo { return $this->belongsTo(Governorate::class); }
    public function areas(): BelongsToMany { return $this->belongsToMany(Area::class, 'branch_area'); }
    public function couriers(): HasMany { return $this->hasMany(Courier::class); }
    public function user(): HasOne { return $this->hasOne(User::class, 'branch_id'); }

    // ✅ علاقة الأوراق المالية (مهمة جداً للخصم والإضافة)
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public static function branchTypes(): array
    {
        return [
            'direct'    => 'مباشر',
            'franchise' => 'امتياز تجاري',
            'hub'       => 'مركز فرز',
        ];
    }
}
