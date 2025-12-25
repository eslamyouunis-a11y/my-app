<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Enums\ShipmentRescheduleReason;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str; // ✅ ضروري عشان الكود المؤقت

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'barcode', 'original_shipment_id',
        'merchant_id', 'branch_id', 'courier_id',
        'receiver_name', 'receiver_phone', 'receiver_phone_alt',
        'governorate_id', 'area_id', 'address',
        'status', 'shipping_type',
        'content', 'weight',
        'is_open_allowed', 'is_fragile', 'is_office_pickup',
        'order_price', 'return_value',
        'base_shipping_fee', 'extra_weight_fee', 'total_shipping_fee',
        'return_fee', 'cancellation_fee',
        'cod_amount', 'merchant_net_amount',
        'delivery_date', 'delivered_at', 'received_from_courier_at', 'received_from_courier_by',
        'accepted_at', 'expected_delivery_at',
        'rescheduled_at', 'rescheduled_for', 'reschedule_reason', 'reschedule_notes',
        'notes', 'return_reason', 'returned_content',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'shipping_type' => ShipmentType::class,
        'is_open_allowed' => 'boolean',
        'is_fragile' => 'boolean',
        'is_office_pickup' => 'boolean',
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'received_from_courier_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expected_delivery_at' => 'datetime',
        'rescheduled_at' => 'datetime',
        'rescheduled_for' => 'datetime',
        'reschedule_reason' => ShipmentRescheduleReason::class,
    ];

    // ===============================================
    // 🔥 الحل النهائي: الباركود = رقم الشحنة (ID)
    // ===============================================
    protected static function booted()
    {
        // 1. قبل الحفظ: بنحط قيمة مؤقتة عشان قاعدة البيانات متطلعش Error
        static::creating(function ($shipment) {
            if (! $shipment->barcode) {
                $shipment->barcode = 'TEMP-' . Str::uuid();
            }
        });

        // 2. بعد الحفظ مباشرة: بناخد الـ ID الحقيقي ونحطه مكان الباركود
        static::created(function ($shipment) {
            if (str_starts_with($shipment->barcode, 'TEMP-')) {
                $shipment->barcode = (string) $shipment->id;
                $shipment->saveQuietly();
            }
        });
    }

    // --- العلاقات ---

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function receivedFromCourierBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_from_courier_by');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function originalShipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'original_shipment_id');
    }

    public function childShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'original_shipment_id');
    }
}
