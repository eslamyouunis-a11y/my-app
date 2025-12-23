<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        // علاقات اختيارية (نضيفها في DB لاحقًا)
        'branch_id',
        'merchant_id',
        'courier_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ===================== Relations ===================== */

    /**
     * المستخدم تابع لفرع (Branch user)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * المستخدم تابع لتاجر (Merchant dashboard)
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * المستخدم تابع لمندوب (Courier dashboard)
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /* ===================== Helpers ===================== */

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isBranchUser(): bool
    {
        return $this->hasRole('branch_user');
    }

    public function isMerchant(): bool
    {
        return $this->hasRole('merchant');
    }

    public function isCourier(): bool
    {
        return $this->hasRole('courier');
    }
}
