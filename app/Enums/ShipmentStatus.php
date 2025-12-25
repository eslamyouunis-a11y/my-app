<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ShipmentStatus: string implements HasLabel, HasColor, HasIcon
{
    case SAVED = 'saved';
    case REQUESTED = 'requested';
    case ACCEPTED = 'accepted';
    case ASSIGNED = 'assigned';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case RESCHEDULED = 'rescheduled';
    case DELIVERED = 'delivered';
    case PARTIAL_DELIVERY = 'partial_delivery';
    case RETURNED_PAID = 'returned_paid';
    case RETURNED_ON_MERCHANT = 'returned_on_merchant';
    case CANCELLED = 'cancelled';
    case RETURNED_TO_BRANCH = 'returned_to_branch';
    case RETURNED_TO_MERCHANT = 'returned_to_merchant';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SAVED => 'محفوظة',
            self::REQUESTED => 'مطلوبة',
            self::ACCEPTED => 'في المخزن',
            self::ASSIGNED => 'مسندة',
            self::OUT_FOR_DELIVERY => 'مع المندوب',
            self::RESCHEDULED => 'مؤجلة',
            self::DELIVERED => 'تم التسليم',
            self::PARTIAL_DELIVERY => 'تسليم جزئي',
            self::RETURNED_PAID => 'مرتجع مدفوع',
            self::RETURNED_ON_MERCHANT => 'مرتجع على الراسل',
            self::CANCELLED => 'ملغاة',
            self::RETURNED_TO_BRANCH => 'مرتجع للفرع',
            self::RETURNED_TO_MERCHANT => 'مرتجع للتاجر',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SAVED, self::REQUESTED => 'gray',
            self::ACCEPTED, self::ASSIGNED => 'info',
            self::OUT_FOR_DELIVERY, self::RESCHEDULED => 'warning',
            self::DELIVERED, self::RETURNED_PAID => 'success',
            self::PARTIAL_DELIVERY => 'primary',
            self::RETURNED_ON_MERCHANT, self::CANCELLED => 'danger',
            self::RETURNED_TO_BRANCH => 'orange',
            self::RETURNED_TO_MERCHANT => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SAVED => 'heroicon-m-document',
            self::REQUESTED => 'heroicon-m-arrow-up-on-square',
            self::ACCEPTED => 'heroicon-m-check-circle',
            self::ASSIGNED => 'heroicon-m-user-plus',
            self::OUT_FOR_DELIVERY => 'heroicon-m-truck',
            self::RESCHEDULED => 'heroicon-m-clock',
            self::DELIVERED => 'heroicon-m-check-badge',
            self::PARTIAL_DELIVERY => 'heroicon-m-adjustments-horizontal',
            self::RETURNED_PAID => 'heroicon-m-currency-dollar',
            self::RETURNED_ON_MERCHANT => 'heroicon-m-arrow-uturn-left',
            self::CANCELLED => 'heroicon-m-x-circle',
            self::RETURNED_TO_BRANCH => 'heroicon-m-arrow-uturn-right',
            self::RETURNED_TO_MERCHANT => 'heroicon-m-arrow-uturn-left',
        };
    }
}
