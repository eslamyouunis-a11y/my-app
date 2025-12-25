<?php

use App\Models\Area;
use App\Models\Branch;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\MerchantSpecialPrice;
use App\Models\ShippingFee;
use App\Models\ShippingZoneFee;
use App\Services\PricingService;

it('calculates pricing using merchant special price for area first', function () {
    $fromGov = Governorate::create(['name' => 'Gov A']);
    $toGov = Governorate::create(['name' => 'Gov B']);
    $area = Area::create(['name' => 'Area 1', 'governorate_id' => $toGov->id]);

    $branch = Branch::create([
        'name' => 'Branch 1',
        'branch_type' => 'direct',
        'governorate_id' => $fromGov->id,
        'manager_name' => 'Manager',
        'manager_phone' => '01000000000',
        'email' => 'branch@example.com',
        'address' => 'Address',
        'is_active' => true,
    ]);

    $merchant = Merchant::create([
        'name' => 'Merchant A',
        'email' => 'merchant@example.com',
        'branch_id' => $branch->id,
        'contact_person_name' => 'Contact',
        'contact_person_phone' => '01011111111',
        'extra_weight_price' => 10,
        'paid_return_fee' => 5,
        'cancellation_fee' => 2,
        'is_active' => true,
    ]);

    MerchantSpecialPrice::create([
        'merchant_id' => $merchant->id,
        'governorate_id' => $toGov->id,
        'area_id' => $area->id,
        'delivery_fee' => 50,
        'office_delivery_fee' => 40,
    ]);

    ShippingFee::create([
        'from_governorate_id' => $fromGov->id,
        'to_governorate_id' => $toGov->id,
        'home_price' => 100,
        'home_sla_days' => 2,
        'office_price' => 80,
        'office_sla_days' => 1,
        'is_active' => true,
    ]);

    $pricing = app(PricingService::class)->calculate($merchant, $toGov->id, $area->id, 2.5, false);

    expect($pricing['base_fee'])->toBe(50.0)
        ->and($pricing['extra_weight_fee'])->toBe(15.0)
        ->and($pricing['total_shipping_fee'])->toBe(65.0)
        ->and($pricing['return_fee'])->toBe(5.0)
        ->and($pricing['cancellation_fee'])->toBe(2.0);
});

it('falls back to zone fee then shipping fee', function () {
    $fromGov = Governorate::create(['name' => 'Gov C']);
    $toGov = Governorate::create(['name' => 'Gov D']);
    $area = Area::create(['name' => 'Area 2', 'governorate_id' => $toGov->id]);

    $branch = Branch::create([
        'name' => 'Branch 2',
        'branch_type' => 'direct',
        'governorate_id' => $fromGov->id,
        'manager_name' => 'Manager',
        'manager_phone' => '01022222222',
        'email' => 'branch2@example.com',
        'address' => 'Address',
        'is_active' => true,
    ]);

    $merchant = Merchant::create([
        'name' => 'Merchant B',
        'email' => 'merchant2@example.com',
        'branch_id' => $branch->id,
        'contact_person_name' => 'Contact',
        'contact_person_phone' => '01033333333',
        'extra_weight_price' => 12,
        'paid_return_fee' => 0,
        'cancellation_fee' => 0,
        'is_active' => true,
    ]);

    $shippingFee = ShippingFee::create([
        'from_governorate_id' => $fromGov->id,
        'to_governorate_id' => $toGov->id,
        'home_price' => 90,
        'home_sla_days' => 2,
        'office_price' => 70,
        'office_sla_days' => 1,
        'is_active' => true,
    ]);

    ShippingZoneFee::create([
        'shipping_fee_id' => $shippingFee->id,
        'area_id' => $area->id,
        'home_price' => 60,
        'home_sla_days' => 2,
        'is_active' => true,
    ]);

    $pricing = app(PricingService::class)->calculate($merchant, $toGov->id, $area->id, 1, true);

    expect($pricing['base_fee'])->toBe(60.0)
        ->and($pricing['extra_weight_fee'])->toBe(0.0)
        ->and($pricing['total_shipping_fee'])->toBe(60.0);
});
