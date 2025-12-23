<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ShipmentType: string implements HasLabel
{
    case NORMAL = 'normal';         // تسليم عادي
    case EXCHANGE = 'exchange';     // استبدال (هسلم واستلم)
    case RETURN_PICKUP = 'return_pickup'; // استرجاع (هروح استلم من العميل)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NORMAL => 'تسليم عادي',
            self::EXCHANGE => 'استبدال',
            self::RETURN_PICKUP => 'استرجاع (Pickup)',
        };
    }
}
