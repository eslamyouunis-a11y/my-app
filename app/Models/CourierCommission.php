<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierCommission extends Model
{
    protected $table = 'courier_commissions';

    protected $fillable = [
        'courier_id',

        // 1. تسليم (Delivery)
        'delivery_value',
        'delivery_percentage',

        // 2. مرتجع مدفوع (Paid Return)
        'paid_value',
        'paid_percentage',

        // 3. مرتجع على الراسل (Return On Sender)
        'sender_return_value',
        'sender_return_percentage',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
