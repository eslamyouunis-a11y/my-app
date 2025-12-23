<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierCommission extends Model
{
    protected $table = 'courier_commissions';

    protected $fillable = [
        'courier_id',
        'delivery_value',
        'delivery_percentage',
        'paid_value',
        'paid_percentage',
        'sender_return_value',
        'sender_return_percentage',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
