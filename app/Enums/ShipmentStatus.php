<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ShipmentStatus: string implements HasLabel, HasColor, HasIcon
{
    case SAVED = 'saved';                     // محفوظة (قبل القبول)
    case ACCEPTED = 'accepted';               // مقبولة (في الفرع)
    case ASSIGNED = 'assigned';               // معينة لمندوب (لسه في الفرع)
    case OUT_FOR_DELIVERY = 'out_for_delivery'; // مسلمة للمندوب (خرجت)
    case DELIVERED = 'delivered';             // تم التسليم
    case PARTIAL_DELIVERY = 'partial_delivery'; // تسليم جزئي (بيتولد عنها شحنة R)
    case RESCHEDULED = 'rescheduled';         // مؤجلة (مع المندوب)
    case RETURNED_TO_BRANCH = 'returned_to_branch'; // مرتجع للفرع (فشل تسليم)
    case RETURNED_TO_MERCHANT = 'returned_to_merchant'; // مرتجع للتاجر (خلصت)
    case CANCELLED = 'cancelled';             // ملغاة (من التاجر قبل الخروج)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SAVED => 'محفوظة (مسودة)',
            self::ACCEPTED => 'مقبولة (بالمخزن)',
            self::ASSIGNED => 'معينة لمندوب',
            self::OUT_FOR_DELIVERY => 'خرجت للتسليم',
            self::DELIVERED => 'تم التسليم',
            self::PARTIAL_DELIVERY => 'تسليم جزئي',
            self::RESCHEDULED => 'مؤجلة',
            self::RETURNED_TO_BRANCH => 'مرتجع للفرع',
            self::RETURNED_TO_MERCHANT => 'مرتجع للتاجر',
            self::CANCELLED => 'ملغاة',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SAVED, self::ACCEPTED => 'gray',
            self::ASSIGNED => 'info',
            self::OUT_FOR_DELIVERY => 'warning',
            self::DELIVERED => 'success',
            self::PARTIAL_DELIVERY, self::RESCHEDULED => 'primary',
            self::RETURNED_TO_BRANCH, self::RETURNED_TO_MERCHANT, self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SAVED => 'heroicon-m-document',
            self::ACCEPTED => 'heroicon-m-check-circle',
            self::ASSIGNED => 'heroicon-m-user',
            self::OUT_FOR_DELIVERY => 'heroicon-m-truck',
            self::DELIVERED => 'heroicon-m-check-badge',
            self::RESCHEDULED => 'heroicon-m-clock',
            self::RETURNED_TO_BRANCH => 'heroicon-m-arrow-u-turn-left',
            default => 'heroicon-m-cube',
        };
    }
}
