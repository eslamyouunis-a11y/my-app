<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ShipmentRescheduleReason: string implements HasLabel, HasColor, HasIcon
{
    case CUSTOMER_REQUEST = 'customer_request';
    case CUSTOMER_UNAVAILABLE = 'customer_unavailable';
    case NO_ANSWER = 'no_answer';
    case ADDRESS_ISSUE = 'address_issue';
    case COURIER_DELAY = 'courier_delay';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CUSTOMER_REQUEST => 'طلب العميل',
            self::CUSTOMER_UNAVAILABLE => 'العميل غير متاح',
            self::NO_ANSWER => 'لا يوجد رد',
            self::ADDRESS_ISSUE => 'مشكلة في العنوان',
            self::COURIER_DELAY => 'تأخير المندوب',
            self::OTHER => 'أسباب أخرى',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CUSTOMER_REQUEST => 'info',
            self::CUSTOMER_UNAVAILABLE, self::NO_ANSWER => 'warning',
            self::ADDRESS_ISSUE => 'danger',
            self::COURIER_DELAY => 'gray',
            self::OTHER => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CUSTOMER_REQUEST => 'heroicon-m-phone-arrow-up-right',
            self::CUSTOMER_UNAVAILABLE => 'heroicon-m-user-minus',
            self::NO_ANSWER => 'heroicon-m-phone-x-mark',
            self::ADDRESS_ISSUE => 'heroicon-m-map-pin',
            self::COURIER_DELAY => 'heroicon-m-truck',
            self::OTHER => 'heroicon-m-ellipsis-horizontal',
        };
    }
}
