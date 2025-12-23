<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    protected $fillable = [
        'idempotency_key',
        'source_type',
        'source_id',
        'title',
        'description',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
