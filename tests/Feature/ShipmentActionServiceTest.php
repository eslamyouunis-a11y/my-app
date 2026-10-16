<?php

namespace Tests\Feature;

use App\Enums\ShipmentRescheduleReason;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Filament\Admin\Resources\ShipmentResource\Pages\ManageShipments;
use App\Filament\Admin\Resources\ShipmentResource\Pages\ViewShipment;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Courier;
use App\Models\CourierCommission;
use App\Models\Governorate;
use App\Models\Merchant;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ShipmentActionService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
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

        $returnShipment = Shipment::query()->where('barcode', $shipment->barcode.'R')->first();
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

    #[DataProvider('reschedulableStatuses')]
    public function test_rescheduling_preserves_custody_and_can_be_repeated(ShipmentStatus $initial, ShipmentStatus $expected): void
    {
        $data = $this->seedCoreData();
        $shipment = $this->createScheduledShipment($data, $initial);
        $courierId = $shipment->courier_id;
        $acceptedAt = $shipment->accepted_at->toDateTimeString();
        $service = app(ShipmentActionService::class);

        foreach (['2026-11-02 14:30:00', '2026-11-03 16:00:00'] as $date) {
            $service->handleReschedule($shipment, $this->rescheduleData($date));
            $shipment->refresh();

            $this->assertSame($expected, $shipment->status);
            $this->assertSame($courierId, $shipment->courier_id);
            $this->assertSame($data['branch']->id, $shipment->branch_id);
            $this->assertSame($acceptedAt, $shipment->accepted_at->toDateTimeString());
            $this->assertSame($date, $shipment->expected_delivery_at->toDateTimeString());
            $this->assertSame($date, $shipment->rescheduled_for->toDateTimeString());
            $this->assertSame(substr($date, 0, 10), $shipment->delivery_date->toDateString());
            $this->assertSame(ShipmentRescheduleReason::CUSTOMER_REQUEST, $shipment->reschedule_reason);
            $this->assertSame('Call before delivery', $shipment->reschedule_notes);
            $this->assertNotNull($shipment->rescheduled_at);
            $this->assertNull($shipment->delivered_at);
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    #[DataProvider('reschedulableStatuses')]
    public function test_postponed_shipment_is_deliverable_only_after_handover(ShipmentStatus $initial, ShipmentStatus $expected): void
    {
        $data = $this->seedCoreData();
        $shipment = $this->createScheduledShipment($data, $initial);
        $service = app(ShipmentActionService::class);
        $service->handleReschedule($shipment, $this->rescheduleData());
        $shipment->refresh();
        $rescheduledAt = $shipment->rescheduled_at->toDateTimeString();

        if (in_array($initial, [ShipmentStatus::ACCEPTED, ShipmentStatus::ASSIGNED], true)) {
            $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleDelivery($shipment, ['cod_amount' => 100]), 'status');
            $this->assertDatabaseCount('transactions', 0);

            if ($initial === ShipmentStatus::ACCEPTED) {
                $shipment->update(['courier_id' => $data['courier']->id, 'status' => ShipmentStatus::ASSIGNED]);
            }
            $service->handleCourierHandover($shipment);
            $this->assertSame(ShipmentStatus::OUT_FOR_DELIVERY, $shipment->fresh()->status);
        }

        $service->handleDelivery($shipment, ['cod_amount' => 100]);
        $shipment->refresh();
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->status);
        $this->assertSame($data['courier']->id, $shipment->courier_id);
        $this->assertSame($data['branch']->id, $shipment->branch_id);
        $this->assertSame('2026-11-02 14:30:00', $shipment->expected_delivery_at->toDateTimeString());
        $this->assertSame('2026-11-02 14:30:00', $shipment->rescheduled_for->toDateTimeString());
        $this->assertSame('2026-11-02', $shipment->delivery_date->toDateString());
        $this->assertSame($rescheduledAt, $shipment->rescheduled_at->toDateTimeString());
        $this->assertSame('Call before delivery', $shipment->reschedule_notes);
        $this->assertSame(ShipmentRescheduleReason::CUSTOMER_REQUEST, $shipment->reschedule_reason);
        $this->assertNotNull($shipment->delivered_at);
        $this->assertEquals(100, $data['courier']->fresh()->custody_balance);
        $this->assertEquals(100, $data['merchant']->fresh()->balance);

        $transactionCount = Transaction::count();
        $service->handleDelivery($shipment, ['cod_amount' => 100]);
        $this->assertDatabaseCount('transactions', $transactionCount);
        $this->assertEquals(100, $data['courier']->fresh()->custody_balance);
    }

    public static function reschedulableStatuses(): array
    {
        return [
            'warehouse' => [ShipmentStatus::ACCEPTED, ShipmentStatus::ACCEPTED],
            'assigned' => [ShipmentStatus::ASSIGNED, ShipmentStatus::ASSIGNED],
            'out for delivery' => [ShipmentStatus::OUT_FOR_DELIVERY, ShipmentStatus::RESCHEDULED],
            'already rescheduled' => [ShipmentStatus::RESCHEDULED, ShipmentStatus::RESCHEDULED],
        ];
    }

    public function test_other_statuses_cannot_be_rescheduled_or_delivered(): void
    {
        $data = $this->seedCoreData();
        $service = app(ShipmentActionService::class);

        foreach (ShipmentStatus::cases() as $status) {
            if (in_array($status, [ShipmentStatus::ACCEPTED, ShipmentStatus::ASSIGNED, ShipmentStatus::OUT_FOR_DELIVERY, ShipmentStatus::RESCHEDULED], true)) {
                continue;
            }
            $shipment = $this->createScheduledShipment($data, $status);
            $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleReschedule($shipment, $this->rescheduleData()), 'status');
            if ($status !== ShipmentStatus::DELIVERED) {
                $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleDelivery($shipment, []), 'status');
            }
        }
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_invalid_reschedule_input_does_not_erase_existing_schedule(): void
    {
        $data = $this->seedCoreData();
        $shipment = $this->createScheduledShipment($data, ShipmentStatus::OUT_FOR_DELIVERY);
        $service = app(ShipmentActionService::class);
        $service->handleReschedule($shipment, $this->rescheduleData());
        $shipment->refresh();

        foreach ([[], ['rescheduled_for' => null], ['rescheduled_for' => ''], ['rescheduled_for' => 'not-a-date'], ['rescheduled_for' => '2026-11-01 12:00:00']] as $input) {
            $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleReschedule($shipment, $input + ['reschedule_reason' => 'customer_request']), 'rescheduled_for');
        }
        $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleReschedule($shipment, ['reschedule_reason' => 'invalid'] + $this->rescheduleData()), 'reschedule_reason');
        $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleReschedule($shipment, ['rescheduled_for' => '2026-11-03']), 'reschedule_reason');
    }

    public function test_legacy_delivery_date_is_used_when_expected_delivery_time_is_missing(): void
    {
        $shipment = $this->createScheduledShipment($this->seedCoreData(), ShipmentStatus::ACCEPTED);
        $shipment->update(['expected_delivery_at' => null, 'delivery_date' => '2026-11-03']);
        $this->assertRejectedWithoutChanges($shipment, fn () => app(ShipmentActionService::class)->handleReschedule($shipment, $this->rescheduleData()), 'rescheduled_for');
    }

    public function test_transitions_validate_the_locked_database_state_not_a_stale_model(): void
    {
        $shipment = $this->createScheduledShipment($this->seedCoreData(), ShipmentStatus::OUT_FOR_DELIVERY);
        $service = app(ShipmentActionService::class);
        Shipment::whereKey($shipment->id)->update(['status' => ShipmentStatus::ASSIGNED]);
        $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleDelivery($shipment, []), 'status');

        $service->handleReschedule($shipment, $this->rescheduleData());
        $this->assertSame(ShipmentStatus::ASSIGNED, $shipment->fresh()->status);
        Shipment::whereKey($shipment->id)->update(['status' => ShipmentStatus::CANCELLED]);
        $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleReschedule($shipment, $this->rescheduleData()), 'status');
        $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleCourierHandover($shipment), 'status');
    }

    public function test_handover_requires_an_assignment_and_a_courier(): void
    {
        $data = $this->seedCoreData();
        $service = app(ShipmentActionService::class);
        foreach ([ShipmentStatus::ACCEPTED, ShipmentStatus::RESCHEDULED, ShipmentStatus::DELIVERED] as $status) {
            $shipment = $this->createScheduledShipment($data, $status);
            $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleCourierHandover($shipment), 'status');
        }
        $shipment->update(['status' => ShipmentStatus::ASSIGNED, 'courier_id' => null]);
        $this->assertRejectedWithoutChanges($shipment, fn () => $service->handleCourierHandover($shipment), 'status');
    }

    public function test_return_outcomes_require_handover_and_work_after_rescheduling(): void
    {
        $data = $this->seedCoreData();
        $service = app(ShipmentActionService::class);
        foreach (['handleReturnPaid' => ShipmentStatus::RETURNED_PAID, 'handleReturnOnMerchant' => ShipmentStatus::RETURNED_ON_MERCHANT] as $method => $expected) {
            foreach ([ShipmentStatus::ACCEPTED, ShipmentStatus::ASSIGNED] as $status) {
                $shipment = $this->createScheduledShipment($data, $status);
                $service->handleReschedule($shipment, $this->rescheduleData());
                $this->assertRejectedWithoutChanges($shipment, fn () => $service->$method($shipment, ['return_value' => 150]), 'status');
            }
            $shipment = $this->createScheduledShipment($data, ShipmentStatus::OUT_FOR_DELIVERY);
            $service->handleReschedule($shipment, $this->rescheduleData());
            $service->$method($shipment, ['return_value' => 150]);
            $this->assertSame($expected, $shipment->fresh()->status);
            $this->assertSame($data['courier']->id, $shipment->fresh()->courier_id);
            $count = Transaction::count();
            $service->$method($shipment, ['return_value' => 150]);
            $this->assertDatabaseCount('transactions', $count);
        }
    }

    #[DataProvider('reschedulableStatuses')]
    public function test_detail_actions_follow_custody_after_rescheduling(ShipmentStatus $initial, ShipmentStatus $expected): void
    {
        $shipment = $this->createScheduledShipment($this->seedCoreData(), $initial);
        $this->signInToAdminPanel();
        $page = Livewire::test(ViewShipment::class, ['record' => $shipment->getRouteKey()]);
        $page->assertActionVisible('reschedule')
            ->callAction('reschedule', $this->rescheduleData())
            ->assertHasNoActionErrors();
        $this->assertSame($expected, $shipment->fresh()->status);

        // Assert the same mounted page updates its actions immediately.
        $page->assertActionVisible('reschedule');
        foreach (['deliver', 'return_paid', 'return_on_merchant'] as $action) {
            $initial->isWithCourier() ? $page->assertActionVisible($action) : $page->assertActionHidden($action);
        }
        $initial === ShipmentStatus::ASSIGNED ? $page->assertActionVisible('handover_courier') : $page->assertActionHidden('handover_courier');
        $initial === ShipmentStatus::ACCEPTED ? $page->assertActionVisible('assign_courier') : $page->assertActionHidden('assign_courier');
        $page->callAction('reschedule', $this->rescheduleData('2026-11-03 16:00:00'))
            ->assertHasNoActionErrors();
        $this->assertSame('2026-11-03 16:00:00', $shipment->fresh()->rescheduled_for->toDateTimeString());
    }

    #[DataProvider('reschedulableStatuses')]
    public function test_table_actions_follow_custody_after_rescheduling(ShipmentStatus $initial, ShipmentStatus $expected): void
    {
        $shipment = $this->createScheduledShipment($this->seedCoreData(), $initial);
        $this->signInToAdminPanel();
        $page = Livewire::test(ManageShipments::class)
            ->set('selectedTableRecords', [(string) $shipment->id]);
        $page->assertTableBulkActionVisible('reschedule')
            ->callTableBulkAction('reschedule', [$shipment], $this->rescheduleData())
            ->assertHasNoTableBulkActionErrors();
        $this->assertSame($expected, $shipment->fresh()->status);

        $page->set('selectedTableRecords', [(string) $shipment->id])->assertTableBulkActionVisible('reschedule');
        foreach (['deliver', 'return_paid', 'return_on_merchant'] as $action) {
            $initial->isWithCourier() ? $page->assertTableBulkActionVisible($action) : $page->assertTableBulkActionHidden($action);
        }
        $initial === ShipmentStatus::ASSIGNED ? $page->assertTableBulkActionVisible('handover_courier') : $page->assertTableBulkActionHidden('handover_courier');
        $initial === ShipmentStatus::ACCEPTED ? $page->assertTableBulkActionVisible('assign_courier') : $page->assertTableBulkActionHidden('assign_courier');
        $page->callTableBulkAction('reschedule', [$shipment], $this->rescheduleData('2026-11-03 16:00:00'))
            ->assertHasNoTableBulkActionErrors();
        $this->assertSame('2026-11-03 16:00:00', $shipment->fresh()->rescheduled_for->toDateTimeString());
    }

    public function test_admin_can_assign_handover_and_deliver_a_postponed_warehouse_shipment(): void
    {
        $data = $this->seedCoreData();
        $shipment = $this->createScheduledShipment($data, ShipmentStatus::ACCEPTED);
        $this->signInToAdminPanel();
        $page = Livewire::test(ViewShipment::class, ['record' => $shipment->getRouteKey()]);
        $page->callAction('reschedule', $this->rescheduleData())->assertHasNoActionErrors()
            ->assertActionHidden('deliver')
            ->callAction('assign_courier', ['courier_id' => $data['courier']->id])->assertHasNoActionErrors()
            ->assertActionHidden('deliver')
            ->callAction('handover_courier')->assertHasNoActionErrors()
            ->assertActionVisible('deliver')
            ->callAction('deliver', ['cod_amount' => 100])->assertHasNoActionErrors()
            ->assertActionHidden('deliver')->assertActionHidden('reschedule');
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->fresh()->status);
        $this->assertSame($data['courier']->id, $shipment->fresh()->courier_id);
        $this->assertSame('2026-11-02 14:30:00', $shipment->fresh()->expected_delivery_at->toDateTimeString());
    }

    public function test_admin_table_can_handover_and_deliver_a_postponed_assigned_shipment(): void
    {
        $data = $this->seedCoreData();
        $shipment = $this->createScheduledShipment($data, ShipmentStatus::ASSIGNED);
        $this->signInToAdminPanel();
        $page = Livewire::test(ManageShipments::class)
            ->set('selectedTableRecords', [(string) $shipment->id]);
        $page->callTableBulkAction('reschedule', [$shipment], $this->rescheduleData())->assertHasNoTableBulkActionErrors()
            ->assertTableBulkActionHidden('deliver')
            ->callTableBulkAction('handover_courier', [$shipment])->assertHasNoTableBulkActionErrors()
            ->assertTableBulkActionVisible('deliver')
            ->callTableBulkAction('deliver', [$shipment], ['cod_amount' => 100])->assertHasNoTableBulkActionErrors()
            ->assertTableBulkActionHidden('deliver')->assertTableBulkActionHidden('reschedule');
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->fresh()->status);
        $this->assertSame($data['courier']->id, $shipment->fresh()->courier_id);
        $this->assertSame('2026-11-02 14:30:00', $shipment->fresh()->expected_delivery_at->toDateTimeString());
    }

    private function signInToAdminPanel(): void
    {
        $role = Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function assertRejectedWithoutChanges(Shipment $shipment, callable $action, string $field): void
    {
        $before = $shipment->fresh()->getRawOriginal();
        $transactions = Transaction::count();
        try {
            $action();
            $this->fail('Expected transition validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertSame($before, $shipment->fresh()->getRawOriginal());
        $this->assertDatabaseCount('transactions', $transactions);
    }

    private function rescheduleData(string $date = '2026-11-02 14:30:00'): array
    {
        return [
            'rescheduled_for' => $date,
            'reschedule_reason' => 'customer_request',
            'reschedule_notes' => '  Call before delivery  ',
        ];
    }

    private function createScheduledShipment(array $data, ShipmentStatus $status): Shipment
    {
        return Shipment::create([
            'merchant_id' => $data['merchant']->id,
            'branch_id' => $data['branch']->id,
            'courier_id' => $status === ShipmentStatus::ACCEPTED ? null : $data['courier']->id,
            'receiver_name' => 'Receiver',
            'receiver_phone' => '01000000001',
            'governorate_id' => $data['governorate']->id,
            'area_id' => $data['area']->id,
            'address' => 'Address',
            'status' => $status,
            'shipping_type' => ShipmentType::NORMAL,
            'order_price' => 100,
            'accepted_at' => '2026-10-30 12:00:00',
            'expected_delivery_at' => '2026-11-01 12:00:00',
            'delivery_date' => '2026-11-01',
        ]);
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
