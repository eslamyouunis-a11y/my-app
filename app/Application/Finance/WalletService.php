<?php

namespace App\Application\Finance;

use App\Domain\Finance\Enums\WalletType;
use App\Domain\Finance\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

class WalletService
{
    public function getOrCreate(Model $owner, WalletType $type): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'owner_type' => $owner->getMorphClass(),
                'owner_id'   => $owner->getKey(),
                'type'       => $type->value,
            ],
            [
                'currency' => 'EGP',
                'cached_balance' => 0,
            ]
        );
    }
}
