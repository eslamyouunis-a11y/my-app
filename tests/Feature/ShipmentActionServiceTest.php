<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Courier;
use App\Models\CourierCommission;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\Shipment;
use App\Services\ShipmentActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentActionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_updates_wallets_and_creates_return_shipment(): void
    {
        $data = $this->seedCoreData();

        $shipment = Shipment::create([
            'merchant_id' => $data['merchant']->id,
            'branch_id' => $data['branch']->id,
            'courier_id' => $data['courier']->id,
            'receiver_name' => 'Receiver',
            'receiver_phone' => '01000000001',
            'receiver_phone_alt' => null,
            'governorate_id' => $data['governorate']->id,
            'area_id' => $data['area']->id,
            'address' => 'Address',
            'status' => ShipmentStatus::OUT_FOR_DELIVERY,
            'shipping_type' => ShipmentType::PARTIAL_DELIVERY,
            'content' => 'Original content',
            'weight' => 1,
            'order_price' => 100,
            'total_shipping_fee' => 10,
        ]);

        app(ShipmentActionService::class)->handleDelivery($shipment, [
            'cod_amount' => 100,
            'returned_content' => 'Returned items',
        ]);

        $shipment->refresh();
        $this->assertEquals(ShipmentStatus::DELIVERED, $shipment->status);
        $this->assertEquals(100.0, (float) $shipment->cod_amount);
        $this->assertEquals('Returned items', $shipment->returned_content);

        $this->assertEquals(100.0, (float) $data['merchant']->fresh()->balance);
        $this->assertEquals(100.0, (float) $data['courier']->fresh()->custody_balance);
        $this->assertEquals(5.0, (float) $data['courier']->fresh()->commission_balance);
        $this->assertEquals(100.0, (float) $data['branch']->fresh()->couriers_custody_balance);
        $this->assertEquals(4.0, (float) $data['branch']->fresh()->commission_balance);

        $returnShipment = Shipment::query()->where('barcode', $shipment->barcode . 'R')->first();
        $this->assertNotNull($returnShipment);
        $this->assertEquals(ShipmentStatus::RETURNED_TO_BRANCH, $returnShipment->status);
        $this->assertEquals(ShipmentType::RETURN_PICKUP, $returnShipment->shipping_type);
        $this->assertEquals('Returned items', $returnShipment->returned_content);
        $this->assertEquals($data['courier']->id, $returnShipment->courier_id);
    }

    public function test_return_paid_updates_wallets_and_receipt_clears_custody(): void
    {
        $data = $this->seedCoreData();

        $shipment = Shipment::create([
            'merchant_id' => $data['merchant']->id,
            'branch_id' => $data['branch']->id,
            'courier_id' => $data['courier']->id,
            'receiver_name' => 'Receiver',
            'receiver_phone' => '01000000002',
            'receiver_phone_alt' => null,
            'governorate_id' => $data['governorate']->id,
            'area_id' => $data['area']->id,
            'address' => 'Address',
            'status' => ShipmentStatus::OUT_FOR_DELIVERY,
            'shipping_type' => ShipmentType::NORMAL,
            'content' => 'Content',
            'weight' => 1,
            'order_price' => 200,
            'return_value' => 150,
            'total_shipping_fee' => 20,
        ]);

        app(ShipmentActionService::class)->handleReturnPaid($shipment, [
            'return_value' => 150,
        ]);

        $shipment->refresh();
        $this->assertEquals(ShipmentStatus::RETURNED_PAID, $shipment->status);
        $this->assertEquals(150.0, (float) $shipment->return_value);

        $this->assertEquals(150.0, (float) $data['courier']->fresh()->custody_balance);
        $this->assertEquals(3.0, (float) $data['courier']->fresh()->commission_balance);
        $this->assertEquals(150.0, (float) $data['branch']->fresh()->couriers_custody_balance);
        $this->assertEquals(6.0, (float) $data['branch']->fresh()->commission_balance);
        $this->assertEquals(140.0, (float) $data['merchant']->fresh()->balance);

        app(ShipmentActionService::class)->handleCourierReceipt($shipment, null);

        $this->assertEquals(0.0, (float) $data['courier']->fresh()->custody_balance);
        $this->assertEquals(0.0, (float) $data['branch']->fresh()->couriers_custody_balance);
        $this->assertNotNull($shipment->fresh()->received_from_courier_at);
    }

    public function test_return_on_merchant_debits_merchant_and_adds_commissions(): void
    {
        $data = $this->seedCoreData();

        $shipment = Shipment::create([
            'merchant_id' => $data['merchant']->id,
            'branch_id' => $data['branch']->id,
            'courier_id' => $data['courier']->id,
            'receiver_name' => 'Receiver',
            'receiver_phone' => '01000000003',
            'receiver_phone_alt' => null,
            'governorate_id' => $data['governorate']->id,
            'area_id' => $data['area']->id,
            'address' => 'Address',
            'status' => ShipmentStatus::OUT_FOR_DELIVERY,
            'shipping_type' => ShipmentType::NORMAL,
            'content' => 'Content',
            'weight' => 1,
            'order_price' => 100,
        ]);

        app(ShipmentActionService::class)->handleReturnOnMerchant($shipment);

        $this->assertEquals(-5.0, (float) $data['merchant']->fresh()->balance);
        $this->assertEquals(2.0, (float) $data['courier']->fresh()->commission_balance);
        $this->assertEquals(1.0, (float) $data['branch']->fresh()->commission_balance);
    }

    private function seedCoreData(): array
    {
        $governorate = Governorate::create(['name' => 'Gov']);
        $area = Area::create(['name' => 'Area', 'governorate_id' => $governorate->id]);

        $branch = Branch::create([
            'name' => 'Branch',
            'branch_type' => 'direct',
            'governorate_id' => $governorate->id,
            'manager_name' => 'Manager',
            'manager_phone' => '01000000000',
            'email' => 'branch@example.com',
            'address' => 'Address',
            'normal_delivery_fee' => 4,
            'normal_delivery_percent' => 0,
            'normal_paid_return_fee' => 6,
            'normal_paid_return_percent' => 0,
            'normal_return_on_sender_fee' => 1,
            'normal_return_on_sender_percent' => 0,
            'office_delivery_fee' => 0,
            'office_delivery_percent' => 0,
        ]);

        $merchant = Merchant::create([
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'address' => 'Address',
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'branch_id' => $branch->id,
            'contact_person_name' => 'Contact',
            'contact_person_phone' => '01000000004',
            'extra_weight_price' => 0,
            'paid_return_fee' => 10,
            'paid_return_percent' => 0,
            'return_on_sender_fee' => 5,
            'return_on_sender_percent' => 0,
            'cancellation_fee' => 0,
            'cancellation_percent' => 0,
            'is_active' => true,
        ]);

        $courier = Courier::create([
            'full_name' => 'Courier',
            'phone' => '01000000005',
            'national_id' => '12345678901234',
            'branch_id' => $branch->id,
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'is_active' => true,
        ]);

        CourierCommission::create([
            'courier_id' => $courier->id,
            'delivery_value' => 5,
            'delivery_percentage' => 0,
            'paid_value' => 3,
            'paid_percentage' => 0,
            'sender_return_value' => 2,
            'sender_return_percentage' => 0,
        ]);

        return compact('governorate', 'area', 'branch', 'merchant', 'courier');
    }
}
