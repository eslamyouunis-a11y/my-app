<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = [
        'transactable_type',
        'transactable_id',
        'wallet_type',
        'type', // credit, debit
        'amount',
        'description',
    ];

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }
}
