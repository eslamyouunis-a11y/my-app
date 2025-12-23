<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
}
