<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{
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

        // ✅ عمولات تسليم عادي
        'normal_delivery_fee',
        'normal_delivery_percent',
        'normal_cod_fee',
        'normal_cod_percent',
        'normal_paid_return_fee',
        'normal_paid_return_percent',

        // ✅ عمولات تسليم مكتب
        'office_delivery_fee',
        'office_delivery_percent',
        'office_cod_fee',
        'office_cod_percent',
        'office_paid_return_fee',
        'office_paid_return_percent',
    ];

    protected static function booted()
    {
        static::creating(function (Branch $branch) {
            if (! $branch->branch_number) {
                $branch->branch_number = (self::max('branch_number') ?? 0) + 1;
            }
        });
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'branch_area');
    }

    public function couriers(): HasMany
    {
        return $this->hasMany(Courier::class);
    }

    // ✅ ربط User الفرع (على users.branch_id)
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'branch_id');
    }

    public static function branchTypes(): array
    {
        return [
            'direct'    => 'مباشر',
            'franchise' => 'امتياز تجاري',
            'hub'       => 'مركز فرز',
        ];
    }

    public function getBranchTypeLabelAttribute(): string
    {
        return self::branchTypes()[$this->branch_type] ?? $this->branch_type;
    }
}
