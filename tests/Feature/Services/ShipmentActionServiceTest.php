<?php

use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\Shipment;
use App\Services\ShipmentActionService;

it('delivers shipment and creates return pickup for exchange', function () {
    $governorate = Governorate::create(['name' => 'Gov E']);
    $area = Area::create(['name' => 'Area 3', 'governorate_id' => $governorate->id]);

    $branch = Branch::create([
        'name' => 'Branch 3',
        'branch_type' => 'direct',
        'governorate_id' => $governorate->id,
        'manager_name' => 'Manager',
        'manager_phone' => '01044444444',
        'email' => 'branch3@example.com',
        'address' => 'Address',
        'is_active' => true,
    ]);

    $merchant = Merchant::create([
        'name' => 'Merchant C',
        'email' => 'merchant3@example.com',
        'branch_id' => $branch->id,
        'contact_person_name' => 'Contact',
        'contact_person_phone' => '01055555555',
        'extra_weight_price' => 8,
        'paid_return_fee' => 3,
        'cancellation_fee' => 1,
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'merchant_id' => $merchant->id,
        'branch_id' => $branch->id,
        'receiver_name' => 'Receiver',
        'receiver_phone' => '01066666666',
        'receiver_phone_alt' => null,
        'governorate_id' => $governorate->id,
        'area_id' => $area->id,
        'address' => 'Receiver Address',
        'status' => ShipmentStatus::OUT_FOR_DELIVERY,
        'shipping_type' => ShipmentType::EXCHANGE,
        'content' => 'Phone',
        'weight' => 2,
        'is_open_allowed' => true,
        'is_fragile' => false,
        'is_office_pickup' => false,
        'order_price' => 150,
        'base_shipping_fee' => 0,
        'extra_weight_fee' => 0,
        'total_shipping_fee' => 0,
        'return_fee' => 0,
        'cancellation_fee' => 0,
        'cod_amount' => 0,
        'merchant_net_amount' => 0,
        'notes' => null,
        'return_reason' => null,
    ]);

    app(ShipmentActionService::class)->handleDelivery($shipment, [
        'cod_amount' => 150,
        'returned_content' => 'Headset',
    ]);

    $shipment->refresh();

    expect($shipment->status)->toBe(ShipmentStatus::DELIVERED)
        ->and((float) $shipment->cod_amount)->toBe(150.0)
        ->and($shipment->delivered_at)->not->toBeNull();

    $child = Shipment::query()->where('original_shipment_id', $shipment->id)->first();

    expect($child)->not->toBeNull()
        ->and($child->barcode)->toBe($shipment->barcode . 'R')
        ->and($child->shipping_type)->toBe(ShipmentType::RETURN_PICKUP)
        ->and($child->status)->toBe(ShipmentStatus::RETURNED_TO_BRANCH)
        ->and($child->content)->toBe('Headset');
});

it('does not create return pickup for normal shipment', function () {
    $governorate = Governorate::create(['name' => 'Gov F']);
    $area = Area::create(['name' => 'Area 4', 'governorate_id' => $governorate->id]);

    $branch = Branch::create([
        'name' => 'Branch 4',
        'branch_type' => 'direct',
        'governorate_id' => $governorate->id,
        'manager_name' => 'Manager',
        'manager_phone' => '01077777777',
        'email' => 'branch4@example.com',
        'address' => 'Address',
        'is_active' => true,
    ]);

    $merchant = Merchant::create([
        'name' => 'Merchant D',
        'email' => 'merchant4@example.com',
        'branch_id' => $branch->id,
        'contact_person_name' => 'Contact',
        'contact_person_phone' => '01088888888',
        'extra_weight_price' => 6,
        'paid_return_fee' => 0,
        'cancellation_fee' => 0,
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'merchant_id' => $merchant->id,
        'branch_id' => $branch->id,
        'receiver_name' => 'Receiver',
        'receiver_phone' => '01099999999',
        'receiver_phone_alt' => null,
        'governorate_id' => $governorate->id,
        'area_id' => $area->id,
        'address' => 'Receiver Address',
        'status' => ShipmentStatus::OUT_FOR_DELIVERY,
        'shipping_type' => ShipmentType::NORMAL,
        'content' => 'Product',
        'weight' => 1,
        'is_open_allowed' => true,
        'is_fragile' => false,
        'is_office_pickup' => false,
        'order_price' => 100,
        'base_shipping_fee' => 0,
        'extra_weight_fee' => 0,
        'total_shipping_fee' => 0,
        'return_fee' => 0,
        'cancellation_fee' => 0,
        'cod_amount' => 0,
        'merchant_net_amount' => 0,
        'notes' => null,
        'return_reason' => null,
    ]);

    app(ShipmentActionService::class)->handleDelivery($shipment, [
        'cod_amount' => 100,
    ]);

    $shipment->refresh();

    $child = Shipment::query()->where('original_shipment_id', $shipment->id)->first();

    expect($shipment->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($child)->toBeNull();
});
