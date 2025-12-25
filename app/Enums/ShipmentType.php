<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ShipmentType: string implements HasLabel
{
    case NORMAL = 'normal';
    case EXCHANGE = 'exchange';
    case RETURN = 'return';
    case PARTIAL_DELIVERY = 'partial_delivery';
    case RETURN_PICKUP = 'return_pickup';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NORMAL => 'عادي',
            self::EXCHANGE => 'استبدال',
            self::RETURN => 'مرتجع',
            self::PARTIAL_DELIVERY => 'تسليم جزئي',
            self::RETURN_PICKUP => 'استلام مرتجع',
        };
    }
}
