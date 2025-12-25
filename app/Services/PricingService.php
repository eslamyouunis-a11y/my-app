<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantSpecialPrice;
use App\Models\ShippingFee;
use App\Models\ShippingZoneFee;

class PricingService
{
    public function calculate(
        Merchant $merchant,
        int $governorateId,
        ?int $areaId,
        float $weight,
        bool $isOfficePickup
    ): array {
        $branch = $merchant->branch;

        $baseFee = 0.0;
        $found = false;

        if ($areaId) {
            $special = MerchantSpecialPrice::query()
                ->where('merchant_id', $merchant->id)
                ->where('governorate_id', $governorateId)
                ->where('area_id', $areaId)
                ->first();

            if ($special) {
                $baseFee = $isOfficePickup ? (float) $special->office_delivery_fee : (float) $special->delivery_fee;
                $found = true;
            }
        }

        if (! $found) {
            $special = MerchantSpecialPrice::query()
                ->where('merchant_id', $merchant->id)
                ->where('governorate_id', $governorateId)
                ->whereNull('area_id')
                ->first();

            if ($special) {
                $baseFee = $isOfficePickup ? (float) $special->office_delivery_fee : (float) $special->delivery_fee;
                $found = true;
            }
        }

        if (! $found && $branch) {
            $shippingFee = ShippingFee::query()
                ->where('from_governorate_id', $branch->governorate_id)
                ->where('to_governorate_id', $governorateId)
                ->first();

            if ($shippingFee) {
                $zoneFee = $areaId
                    ? ShippingZoneFee::query()
                        ->where('shipping_fee_id', $shippingFee->id)
                        ->where('area_id', $areaId)
                        ->first()
                    : null;

                if ($zoneFee) {
                    $baseFee = (float) $zoneFee->home_price;
                } else {
                    $baseFee = $isOfficePickup ? (float) $shippingFee->office_price : (float) $shippingFee->home_price;
                }
            }
        }

        $billableWeight = max($weight - 1, 0);
        $extraWeightFee = $billableWeight * (float) ($merchant->extra_weight_price ?? 0);
        $totalShippingFee = $baseFee + $extraWeightFee;

        $returnFee = (float) ($merchant->paid_return_fee ?? 0);
        $cancellationFee = (float) ($merchant->cancellation_fee ?? 0);

        return [
            'base_fee' => round($baseFee, 2),
            'extra_weight_fee' => round($extraWeightFee, 2),
            'total_shipping_fee' => round($totalShippingFee, 2),
            'return_fee' => round($returnFee, 2),
            'cancellation_fee' => round($cancellationFee, 2),
        ];
    }

    public function resolveSlaDays(
        int $fromGovernorateId,
        int $toGovernorateId,
        ?int $areaId,
        bool $isOfficePickup
    ): ?int {
        $shippingFee = ShippingFee::query()
            ->where('from_governorate_id', $fromGovernorateId)
            ->where('to_governorate_id', $toGovernorateId)
            ->first();

        if (! $shippingFee) {
            return null;
        }

        if ($areaId) {
            $zoneFee = ShippingZoneFee::query()
                ->where('shipping_fee_id', $shippingFee->id)
                ->where('area_id', $areaId)
                ->first();

            if ($zoneFee && ! $isOfficePickup) {
                return (int) $zoneFee->home_sla_days;
            }
        }

        return (int) ($isOfficePickup ? $shippingFee->office_sla_days : $shippingFee->home_sla_days);
    }
}
