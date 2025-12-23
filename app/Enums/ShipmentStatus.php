<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ShipmentStatus: string implements HasLabel, HasColor, HasIcon
{
    // --- 1. مرحلة التجهيز ---
    case SAVED = 'saved';                     // محفوظة (مسودة لسه الراسل مبعتهاش)
    case REQUESTED = 'requested';             // طلب شحن (الراسل عمل طلب بيك اب)
    case ACCEPTED = 'accepted';               // مقبولة (وصلت مخزن الفرع)
    case ASSIGNED = 'assigned';               // معينة لمندوب (في انتظار الخروج)

    // --- 2. مرحلة التوصيل ---
    case OUT_FOR_DELIVERY = 'out_for_delivery'; // خرجت للتسليم (في عهدة المندوب)
    case RESCHEDULED = 'rescheduled';         // مؤجلة (مع المندوب)

    // --- 3. حالات النهاية (التسليم) ---
    case DELIVERED = 'delivered';             // تم التسليم (ناجح)
    case PARTIAL_DELIVERY = 'partial_delivery'; // تسليم جزئي (بيولد شحنة R)

    // --- 4. حالات المرتجعات (المالية) ---
    case RETURNED_PAID = 'returned_paid';     // مرتجع مدفوع (العميل دفع الشحن)
    case RETURNED_ON_MERCHANT = 'returned_on_merchant'; // مرتجع على الراسل (التاجر دفع الشحن)
    case CANCELLED = 'cancelled';             // ملغى / مرتجع إلغاء (قبل خروجها أو وهي مع المندوب)

    // --- 5. العودة للمخزن ---
    case RETURNED_TO_BRANCH = 'returned_to_branch'; // المندوب سلم المرتجع للفرع
    case RETURNED_TO_MERCHANT = 'returned_to_merchant'; // الفرع سلم المرتجع للتاجر (النهاية)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SAVED => 'مسودة',
            self::REQUESTED => 'طلب بيك اب',
            self::ACCEPTED => 'مقبولة بالمخزن',
            self::ASSIGNED => 'معينة لمندوب',
            self::OUT_FOR_DELIVERY => 'خرجت للتسليم',
            self::RESCHEDULED => 'مؤجلة',
            self::DELIVERED => 'تم التسليم',
            self::PARTIAL_DELIVERY => 'تسليم جزئي',
            self::RETURNED_PAID => 'مرتجع مدفوع',
            self::RETURNED_ON_MERCHANT => 'مرتجع على الراسل',
            self::CANCELLED => 'ملغاة',
            self::RETURNED_TO_BRANCH => 'مرتجع بالفرع',
            self::RETURNED_TO_MERCHANT => 'تم الارتجاع للتاجر',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SAVED, self::REQUESTED => 'gray',
            self::ACCEPTED, self::ASSIGNED => 'info',
            self::OUT_FOR_DELIVERY, self::RESCHEDULED => 'warning',
            self::DELIVERED, self::RETURNED_PAID => 'success', // أخضر (فلوس دخلت)
            self::PARTIAL_DELIVERY => 'primary',
            self::RETURNED_ON_MERCHANT, self::CANCELLED => 'danger', // أحمر (خسارة/مشكلة)
            self::RETURNED_TO_BRANCH => 'orange',
            self::RETURNED_TO_MERCHANT => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SAVED => 'heroicon-m-document',
            self::ACCEPTED => 'heroicon-m-check-circle',
            self::OUT_FOR_DELIVERY => 'heroicon-m-truck',
            self::DELIVERED => 'heroicon-m-check-badge',
            self::RETURNED_PAID => 'heroicon-m-currency-dollar',
            self::RETURNED_ON_MERCHANT => 'heroicon-m-x-circle',
            self::RETURNED_TO_MERCHANT => 'heroicon-m-arrow-u-turn-left',
            default => 'heroicon-m-cube',
        };
    }
}
