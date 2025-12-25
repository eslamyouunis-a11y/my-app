<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Models\Branch;
use App\Models\Shipment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ShipmentActionService
{
    public function handleAcceptance(Shipment $shipment, int $branchId): void
    {
        DB::transaction(function () use ($shipment, $branchId) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            $acceptedAt = now();
            $expectedDeliveryAt = $this->calculateExpectedDeliveryAt($shipment, $branchId, $acceptedAt);

            $shipment->update([
                'branch_id' => $branchId,
                'status' => ShipmentStatus::ACCEPTED,
                'accepted_at' => $acceptedAt,
                'expected_delivery_at' => $expectedDeliveryAt,
                'delivery_date' => $expectedDeliveryAt?->toDateString(),
            ]);
        });
    }

    public function handleDelivery(Shipment $shipment, array $data): void
    {
        DB::transaction(function () use ($shipment, $data) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->status === ShipmentStatus::DELIVERED) {
                return;
            }

            $codAmount = array_key_exists('cod_amount', $data)
                ? (float) $data['cod_amount']
                : (float) $shipment->order_price;

            $returnedContent = isset($data['returned_content'])
                ? trim((string) $data['returned_content'])
                : null;

            $updates = [
                'status' => ShipmentStatus::DELIVERED,
                'delivered_at' => now(),
                'cod_amount' => $codAmount,
                'received_from_courier_at' => null,
                'received_from_courier_by' => null,
            ];

            if ($returnedContent !== null && $returnedContent !== '') {
                $updates['returned_content'] = $returnedContent;
            }

            $shipment->update($updates);

            $this->applyDeliveryFinancials($shipment, $codAmount);

            if (! $this->requiresReturnShipment($shipment)) {
                return;
            }

            $this->ensureReturnShipment($shipment, $returnedContent);
        });
    }

    public function handleReturnPaid(Shipment $shipment, array $data): void
    {
        DB::transaction(function () use ($shipment, $data) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->status === ShipmentStatus::RETURNED_PAID) {
                return;
            }

            $returnValue = array_key_exists('return_value', $data)
                ? (float) $data['return_value']
                : (float) $shipment->return_value;

            $shipment->update([
                'status' => ShipmentStatus::RETURNED_PAID,
                'delivered_at' => now(),
                'cod_amount' => $returnValue,
                'return_value' => $returnValue,
                'received_from_courier_at' => null,
                'received_from_courier_by' => null,
            ]);

            $this->applyReturnPaidFinancials($shipment, $returnValue);
        });
    }

    public function handleReturnOnMerchant(Shipment $shipment): void
    {
        DB::transaction(function () use ($shipment) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->status === ShipmentStatus::RETURNED_ON_MERCHANT) {
                return;
            }

            $shipment->update([
                'status' => ShipmentStatus::RETURNED_ON_MERCHANT,
                'delivered_at' => now(),
                'cod_amount' => 0,
                'received_from_courier_at' => null,
                'received_from_courier_by' => null,
            ]);

            $this->applyReturnOnMerchantFinancials($shipment);
        });
    }

    public function handleCourierReceipt(Shipment $shipment, ?int $receivedByUserId = null): void
    {
        DB::transaction(function () use ($shipment, $receivedByUserId) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if (! $shipment->courier_id) {
                return;
            }

            if ($shipment->received_from_courier_at) {
                return;
            }

            $collectable = $this->getCollectableAmountForReceipt($shipment);

            if ($collectable > 0) {
                $this->applyCourierCustody($shipment, 'debit', $collectable, 'Courier handover for shipment ' . $shipment->barcode);
                $this->applyBranchCustody($shipment, 'debit', $collectable, 'Courier handover for shipment ' . $shipment->barcode);
            }

            $shipment->update([
                'received_from_courier_at' => now(),
                'received_from_courier_by' => $receivedByUserId,
            ]);
        });
    }

    private function applyDeliveryFinancials(Shipment $shipment, float $codAmount): void
    {
        $shipment->loadMissing(['merchant', 'courier.commission', 'branch']);

        $type = $shipment->shipping_type instanceof ShipmentType
            ? $shipment->shipping_type->value
            : (string) $shipment->shipping_type;

        if ($type === ShipmentType::RETURN->value) {
            $this->applyReturnDeliveryFinancials($shipment, $codAmount);
            return;
        }

        $collectable = max(0.0, round($codAmount, 2));
        $commissionBase = max(0.0, round(abs($codAmount), 2));

        if ($collectable > 0) {
            $this->applyMerchantBalance($shipment, 'credit', $collectable, 'Shipment delivered: COD for ' . $shipment->barcode);
            $this->applyCourierCustody($shipment, 'credit', $collectable, 'Shipment delivered: COD for ' . $shipment->barcode);
            $this->applyBranchCustody($shipment, 'credit', $collectable, 'Shipment delivered: COD for ' . $shipment->barcode);
        }

        $courierCommission = $this->calculateCourierCommission($shipment, 'delivery', $commissionBase);
        $this->applyCourierCommission($shipment, $courierCommission, 'Shipment delivered: courier commission for ' . $shipment->barcode);

        $branchCommission = $this->calculateBranchCommission($shipment, 'delivery', $commissionBase);
        $this->applyBranchCommission($shipment, $branchCommission, 'Shipment delivered: branch commission for ' . $shipment->barcode);
    }

    private function applyReturnDeliveryFinancials(Shipment $shipment, float $codAmount): void
    {
        $refundAmount = max(0.0, round(abs($codAmount), 2));
        if ($refundAmount <= 0) {
            return;
        }

        $shippingFee = max(0.0, round((float) $shipment->total_shipping_fee, 2));
        $merchantCharge = $refundAmount + $shippingFee;

        $this->applyCourierCustody($shipment, 'debit', $refundAmount, 'Return shipment payout for ' . $shipment->barcode);
        $this->applyBranchCustody($shipment, 'debit', $refundAmount, 'Return shipment payout for ' . $shipment->barcode);
        $this->applyMerchantBalance($shipment, 'debit', $merchantCharge, 'Return shipment charge for ' . $shipment->barcode);

        $courierCommission = $this->calculateCourierCommission($shipment, 'delivery', $refundAmount);
        $this->applyCourierCommission($shipment, $courierCommission, 'Return shipment commission for ' . $shipment->barcode);

        $branchCommission = $this->calculateBranchCommission($shipment, 'delivery', $refundAmount);
        $this->applyBranchCommission($shipment, $branchCommission, 'Return shipment commission for ' . $shipment->barcode);
    }

    private function applyReturnPaidFinancials(Shipment $shipment, float $returnValue): void
    {
        $returnValue = max(0.0, round($returnValue, 2));
        if ($returnValue <= 0) {
            return;
        }

        $shipment->loadMissing(['merchant', 'courier.commission', 'branch']);

        $this->applyCourierCustody($shipment, 'credit', $returnValue, 'Paid return: collected amount for ' . $shipment->barcode);
        $this->applyBranchCustody($shipment, 'credit', $returnValue, 'Paid return: collected amount for ' . $shipment->barcode);

        $courierCommission = $this->calculateCourierCommission($shipment, 'paid_return', $returnValue);
        $this->applyCourierCommission($shipment, $courierCommission, 'Paid return: courier commission for ' . $shipment->barcode);

        $branchCommission = $this->calculateBranchCommission($shipment, 'paid_return', $returnValue);
        $this->applyBranchCommission($shipment, $branchCommission, 'Paid return: branch commission for ' . $shipment->barcode);

        $merchantFee = $this->calculateMerchantFee($shipment, 'paid_return', $returnValue);
        $netToMerchant = round($returnValue - $merchantFee, 2);

        if ($netToMerchant > 0) {
            $this->applyMerchantBalance($shipment, 'credit', $netToMerchant, 'Paid return: net to merchant for ' . $shipment->barcode);
        } elseif ($netToMerchant < 0) {
            $this->applyMerchantBalance($shipment, 'debit', abs($netToMerchant), 'Paid return: shipping fees for ' . $shipment->barcode);
        }
    }

    private function applyReturnOnMerchantFinancials(Shipment $shipment): void
    {
        $shipment->loadMissing(['merchant', 'courier.commission', 'branch']);

        $baseAmount = max(0.0, round(abs((float) $shipment->order_price), 2));
        $merchantFee = $this->calculateMerchantFee($shipment, 'return_on_sender', $baseAmount);

        if ($merchantFee > 0) {
            $this->applyMerchantBalance($shipment, 'debit', $merchantFee, 'Return on merchant: shipping fee for ' . $shipment->barcode);
        }

        $courierCommission = $this->calculateCourierCommission($shipment, 'return_on_sender', $baseAmount);
        $this->applyCourierCommission($shipment, $courierCommission, 'Return on merchant: courier commission for ' . $shipment->barcode);

        $branchCommission = $this->calculateBranchCommission($shipment, 'return_on_sender', $baseAmount);
        $this->applyBranchCommission($shipment, $branchCommission, 'Return on merchant: branch commission for ' . $shipment->barcode);
    }

    private function ensureReturnShipment(Shipment $shipment, ?string $returnedContent): void
    {
        $barcode = $shipment->barcode . 'R';
        $existing = Shipment::query()->where('barcode', $barcode)->first();

        if ($existing) {
            return;
        }

        $content = $returnedContent;
        if ($content === null || $content === '') {
            $content = $shipment->returned_content ?: $shipment->content;
        }

        Shipment::create([
            'barcode' => $barcode,
            'original_shipment_id' => $shipment->id,
            'merchant_id' => $shipment->merchant_id,
            'branch_id' => $shipment->branch_id,
            'courier_id' => $shipment->courier_id,
            'receiver_name' => $shipment->receiver_name,
            'receiver_phone' => $shipment->receiver_phone,
            'receiver_phone_alt' => $shipment->receiver_phone_alt,
            'governorate_id' => $shipment->governorate_id,
            'area_id' => $shipment->area_id,
            'address' => $shipment->address,
            'status' => ShipmentStatus::RETURNED_TO_BRANCH,
            'shipping_type' => ShipmentType::RETURN_PICKUP,
            'content' => $content,
            'weight' => $shipment->weight,
            'is_open_allowed' => $shipment->is_open_allowed,
            'is_fragile' => $shipment->is_fragile,
            'is_office_pickup' => $shipment->is_office_pickup,
            'order_price' => 0,
            'return_value' => 0,
            'base_shipping_fee' => 0,
            'extra_weight_fee' => 0,
            'total_shipping_fee' => 0,
            'return_fee' => 0,
            'cancellation_fee' => 0,
            'cod_amount' => 0,
            'merchant_net_amount' => 0,
            'delivered_at' => now(),
            'received_from_courier_at' => null,
            'received_from_courier_by' => null,
            'notes' => null,
            'return_reason' => null,
            'returned_content' => $content,
        ]);
    }

    private function getCollectableAmountForReceipt(Shipment $shipment): float
    {
        if ($shipment->status === ShipmentStatus::RETURNED_PAID) {
            return max(0.0, round((float) $shipment->return_value, 2));
        }

        if ($shipment->status === ShipmentStatus::DELIVERED) {
            return max(0.0, round((float) $shipment->cod_amount, 2));
        }

        return 0.0;
    }

    private function calculateCourierCommission(Shipment $shipment, string $type, float $baseAmount): float
    {
        $courier = $shipment->courier;
        if (! $courier || ! $courier->commission) {
            return 0.0;
        }

        $commission = $courier->commission;

        return match ($type) {
            'delivery' => $this->calculateFee($commission->delivery_value, $commission->delivery_percentage, $baseAmount),
            'paid_return' => $this->calculateFee($commission->paid_value, $commission->paid_percentage, $baseAmount),
            'return_on_sender' => $this->calculateFee($commission->sender_return_value, $commission->sender_return_percentage, $baseAmount),
            default => 0.0,
        };
    }

    private function calculateBranchCommission(Shipment $shipment, string $type, float $baseAmount): float
    {
        $branch = $shipment->branch;
        if (! $branch) {
            return 0.0;
        }

        return match ($type) {
            'delivery' => $shipment->is_office_pickup
                ? $this->calculateFee($branch->office_delivery_fee, $branch->office_delivery_percent, $baseAmount)
                : $this->calculateFee($branch->normal_delivery_fee, $branch->normal_delivery_percent, $baseAmount),
            'paid_return' => $this->calculateFee($branch->normal_paid_return_fee, $branch->normal_paid_return_percent, $baseAmount),
            'return_on_sender' => $this->calculateFee($branch->normal_return_on_sender_fee, $branch->normal_return_on_sender_percent, $baseAmount),
            default => 0.0,
        };
    }

    private function calculateMerchantFee(Shipment $shipment, string $type, float $baseAmount): float
    {
        $merchant = $shipment->merchant;
        if (! $merchant) {
            return 0.0;
        }

        return match ($type) {
            'paid_return' => $this->calculateFee($merchant->paid_return_fee, $merchant->paid_return_percent, $baseAmount),
            'return_on_sender' => $this->calculateFee($merchant->return_on_sender_fee, $merchant->return_on_sender_percent, $baseAmount),
            default => 0.0,
        };
    }

    private function calculateFee(?float $fixed, ?float $percent, float $baseAmount): float
    {
        $fixedValue = (float) ($fixed ?? 0);
        $percentValue = (float) ($percent ?? 0);
        $base = max(0.0, round(abs($baseAmount), 2));

        return round($fixedValue + ($base * $percentValue / 100), 2);
    }

    private function applyMerchantBalance(Shipment $shipment, string $type, float $amount, string $description): void
    {
        $merchant = $shipment->merchant;
        if (! $merchant) {
            return;
        }

        $this->applyTransaction($merchant, 'balance', $type, $amount, $description);
    }

    private function applyCourierCustody(Shipment $shipment, string $type, float $amount, string $description): void
    {
        $courier = $shipment->courier;
        if (! $courier) {
            return;
        }

        $this->applyTransaction($courier, 'custody_balance', $type, $amount, $description);
    }

    private function applyCourierCommission(Shipment $shipment, float $amount, string $description): void
    {
        $courier = $shipment->courier;
        if (! $courier || $amount <= 0) {
            return;
        }

        $this->applyTransaction($courier, 'commission_balance', 'credit', $amount, $description);
    }

    private function applyBranchCustody(Shipment $shipment, string $type, float $amount, string $description): void
    {
        $branch = $shipment->branch;
        if (! $branch) {
            return;
        }

        $this->applyTransaction($branch, 'couriers_custody_balance', $type, $amount, $description);
    }

    private function applyBranchCommission(Shipment $shipment, float $amount, string $description): void
    {
        $branch = $shipment->branch;
        if (! $branch || $amount <= 0) {
            return;
        }

        $this->applyTransaction($branch, 'commission_balance', 'credit', $amount, $description);
    }

    private function applyTransaction(Model $owner, string $walletColumn, string $type, float $amount, string $description): void
    {
        $normalized = round($amount, 2);
        if ($normalized <= 0) {
            return;
        }

        Transaction::create([
            'transactable_type' => $owner->getMorphClass(),
            'transactable_id' => $owner->getKey(),
            'wallet_type' => $walletColumn,
            'type' => $type,
            'amount' => $normalized,
            'description' => $description,
        ]);

        if ($type === 'credit') {
            $owner->increment($walletColumn, $normalized);
            return;
        }

        $owner->decrement($walletColumn, $normalized);
    }

    private function requiresReturnShipment(Shipment $shipment): bool
    {
        $type = $shipment->shipping_type instanceof ShipmentType
            ? $shipment->shipping_type->value
            : (string) $shipment->shipping_type;

        return in_array($type, ['exchange', 'return', 'partial_delivery'], true);
    }

    public function handleReschedule(Shipment $shipment, array $data): void
    {
        DB::transaction(function () use ($shipment, $data) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            $rescheduledFor = $data['rescheduled_for'] ?? null;
            $rescheduledForAt = $rescheduledFor ? Carbon::parse($rescheduledFor) : null;
            $currentDeliveryAt = $shipment->expected_delivery_at ?? $shipment->delivery_date;
            if ($rescheduledForAt && $currentDeliveryAt) {
                $currentDeliveryAt = $currentDeliveryAt instanceof CarbonInterface
                    ? $currentDeliveryAt
                    : Carbon::parse($currentDeliveryAt);
                if ($rescheduledForAt->copy()->startOfDay()->lt($currentDeliveryAt->copy()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'rescheduled_for' => 'لا يمكن تأجيل الشحنة الى هذا التاريخ لأنه قبل تاريخ التسليم الفعلي المسجل لدينا',
                    ]);
                }
            }

            $notes = isset($data['reschedule_notes'])
                ? trim((string) $data['reschedule_notes'])
                : null;

            $shipment->update([
                'status' => ShipmentStatus::ACCEPTED,
                'rescheduled_at' => now(),
                'rescheduled_for' => $rescheduledForAt,
                'reschedule_reason' => $data['reschedule_reason'] ?? null,
                'reschedule_notes' => $notes !== '' ? $notes : null,
                'expected_delivery_at' => $rescheduledForAt,
                'delivery_date' => $rescheduledForAt?->toDateString(),
            ]);
        });
    }

    private function calculateExpectedDeliveryAt(
        Shipment $shipment,
        int $branchId,
        CarbonInterface $acceptedAt
    ): ?CarbonInterface {
        $branch = Branch::query()->select('id', 'governorate_id')->find($branchId);

        if (! $branch || ! $shipment->governorate_id) {
            return null;
        }

        $slaDays = app(PricingService::class)->resolveSlaDays(
            (int) $branch->governorate_id,
            (int) $shipment->governorate_id,
            $shipment->area_id,
            (bool) $shipment->is_office_pickup
        );

        if ($slaDays === null) {
            return null;
        }

        return $acceptedAt->copy()->addHours($slaDays * 24);
    }
}
