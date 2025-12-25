<?php

namespace App\Filament\Admin\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Enums\ShipmentRescheduleReason;
use App\Filament\Admin\Resources\ShipmentResource;
use App\Models\Branch;
use App\Models\Courier;
use App\Services\ShipmentActionService;
use Carbon\Carbon;
use Closure;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('accept')
                    ->label('قبول الشحنة')
                    ->icon('heroicon-m-check-circle')
                    ->form(function (): array {
                        $user = auth()->user();
                        if ($user?->isAdmin()) {
                            return [
                                Select::make('branch_id')
                                    ->label('الفرع')
                                    ->options(Branch::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ];
                        }

                        return [];
                    })
                    ->action(function (array $data): void {
                        $user = auth()->user();
                        $branchId = $data['branch_id']
                            ?? $user?->branch_id
                            ?? $this->record->merchant?->branch_id;

                        app(ShipmentActionService::class)->handleAcceptance($this->record, $branchId);
                    })
                    ->visible(fn () => $this->record->status === ShipmentStatus::SAVED),

                Action::make('assign_courier')
                    ->label('تعيين مندوب')
                    ->icon('heroicon-m-user-plus')
                    ->form([
                        Select::make('courier_id')
                            ->label('المندوب')
                            ->options(fn () => Courier::query()
                                ->where('branch_id', $this->record->branch_id)
                                ->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->record->update([
                        'courier_id' => $data['courier_id'],
                        'status' => ShipmentStatus::ASSIGNED,
                    ]))
                    ->visible(fn () => $this->record->status === ShipmentStatus::ACCEPTED),

                Action::make('reschedule')
                    ->label('تأجيل')
                    ->icon('heroicon-m-clock')
                    ->form([
                        DateTimePicker::make('rescheduled_for')
                            ->label('تاريخ ووقت إعادة التسليم')
                            ->seconds(false)
                            ->required()
                            ->rule(function () {
                                return function (string $attribute, $value, Closure $fail): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $currentDeliveryAt = $this->record->expected_delivery_at ?? $this->record->delivery_date;
                                    if (! $currentDeliveryAt) {
                                        return;
                                    }

                                    $rescheduledForAt = Carbon::parse($value);
                                    $currentDeliveryAt = $currentDeliveryAt instanceof \Carbon\CarbonInterface
                                        ? $currentDeliveryAt
                                        : Carbon::parse($currentDeliveryAt);

                                    if ($rescheduledForAt->copy()->startOfDay()->lt($currentDeliveryAt->copy()->startOfDay())) {
                                        $fail('لا يمكن تأجيل الشحنة الى هذا التاريخ لأنه قبل تاريخ التسليم الفعلي المسجل لدينا');
                                    }
                                };
                            })
                            ->default(fn () => $this->record->rescheduled_for ?? $this->record->expected_delivery_at ?? now()->addDay()),
                        Select::make('reschedule_reason')
                            ->label('سبب التأجيل')
                            ->options(collect(ShipmentRescheduleReason::cases())->mapWithKeys(
                                fn (ShipmentRescheduleReason $reason) => [$reason->value => $reason->getLabel()]
                            ))
                            ->searchable()
                            ->required(),
                        Textarea::make('reschedule_notes')
                            ->label('تفاصيل التأجيل')
                            ->rows(3),
                    ])
                    ->action(fn (array $data) => app(ShipmentActionService::class)->handleReschedule($this->record, $data))
                    ->visible(fn () => in_array($this->record->status, [
                        ShipmentStatus::ACCEPTED,
                        ShipmentStatus::ASSIGNED,
                        ShipmentStatus::OUT_FOR_DELIVERY,
                    ], true)),

                Action::make('transfer_branch')
                    ->label('تحويل فرع')
                    ->icon('heroicon-m-arrow-path')
                    ->form([
                        Select::make('branch_id')
                            ->label('الفرع')
                            ->options(Branch::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->record->update([
                        'branch_id' => $data['branch_id'],
                        'courier_id' => null,
                        'status' => ShipmentStatus::ACCEPTED,
                    ]))
                    ->visible(fn () => $this->record->status === ShipmentStatus::ACCEPTED),

                Action::make('cancel_return')
                    ->label('مرتجع إلغاء')
                    ->icon('heroicon-m-x-circle')
                    ->action(fn () => $this->record->update([
                        'status' => ShipmentStatus::CANCELLED,
                    ]))
                    ->visible(fn () => in_array($this->record->status, [
                        ShipmentStatus::ACCEPTED,
                        ShipmentStatus::OUT_FOR_DELIVERY,
                        ShipmentStatus::RESCHEDULED,
                    ], true)),

                Action::make('handover_courier')
                    ->label('تسليم للمندوب')
                    ->icon('heroicon-m-truck')
                    ->action(fn () => $this->record->update([
                        'status' => ShipmentStatus::OUT_FOR_DELIVERY,
                    ]))
                    ->visible(fn () => $this->record->status === ShipmentStatus::ASSIGNED),

                Action::make('change_courier')
                    ->label('تغيير مندوب')
                    ->icon('heroicon-m-user-plus')
                    ->form([
                        Select::make('courier_id')
                            ->label('المندوب')
                            ->options(fn () => Courier::query()
                                ->where('branch_id', $this->record->branch_id)
                                ->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->record->update([
                        'courier_id' => $data['courier_id'],
                    ]))
                    ->visible(fn () => $this->record->status === ShipmentStatus::ASSIGNED),

                Action::make('back_to_stock')
                    ->label('رجوع للمخزن')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->action(fn () => $this->record->update([
                        'courier_id' => null,
                        'status' => ShipmentStatus::ACCEPTED,
                    ]))
                    ->visible(fn () => $this->record->status === ShipmentStatus::ASSIGNED),

                Action::make('deliver')
                    ->label('تسليم الشحنة')
                    ->icon('heroicon-m-check-badge')
                    ->form([
                        TextInput::make('cod_amount')
                            ->label('تسليم الشحنة')
                            ->numeric()
                            ->required()
                            ->default(fn () => $this->record->order_price)
                            ->rule(function (): string {
                                $type = $this->record->shipping_type instanceof ShipmentType ? $this->record->shipping_type->value : (string) $this->record->shipping_type;
                                return $type === ShipmentType::RETURN->value ? 'lt:0' : 'gte:0';
                            })
                            ->prefix('جنيه'),
                        Textarea::make('returned_content')
                            ->label('تسليم الشحنة')
                            ->default(fn () => $this->record->returned_content)
                            ->visible(fn () => in_array(
                                $this->record->shipping_type instanceof ShipmentType
                                    ? $this->record->shipping_type->value
                                    : (string) $this->record->shipping_type,
                                ['exchange', 'return', 'partial_delivery'],
                                true
                            ))
                            ->required(fn () => in_array(
                                $this->record->shipping_type instanceof ShipmentType
                                    ? $this->record->shipping_type->value
                                    : (string) $this->record->shipping_type,
                                ['exchange', 'return', 'partial_delivery'],
                                true
                            )),
                    ])
                    ->action(fn (array $data) => app(ShipmentActionService::class)->handleDelivery($this->record, $data))
                    ->visible(fn () => in_array($this->record->status, [
                        ShipmentStatus::OUT_FOR_DELIVERY,
                        ShipmentStatus::RESCHEDULED,
                    ], true)),
                Action::make('return_paid')
                    ->label('مرتجع مدفوع')
                    ->icon('heroicon-m-currency-dollar')
                    ->form([
                        TextInput::make('return_value')
                            ->label('قيمة المرتجع')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->default(fn () => $this->record->return_value)
                            ->prefix('جنيه'),
                    ])
                    ->action(fn (array $data) => app(ShipmentActionService::class)->handleReturnPaid($this->record, $data))
                    ->visible(fn () => in_array($this->record->status, [
                        ShipmentStatus::OUT_FOR_DELIVERY,
                        ShipmentStatus::RESCHEDULED,
                    ], true)),
                Action::make('return_on_merchant')
                    ->label('مرتجع على التاجر')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->action(fn () => app(ShipmentActionService::class)->handleReturnOnMerchant($this->record))
                    ->visible(fn () => in_array($this->record->status, [
                        ShipmentStatus::OUT_FOR_DELIVERY,
                        ShipmentStatus::RESCHEDULED,
                    ], true)),
                Action::make('receive_from_courier')
                    ->label('استلام من المندوب')
                    ->icon('heroicon-m-inbox-arrow-down')
                    ->action(fn () => app(ShipmentActionService::class)->handleCourierReceipt($this->record, auth()->id()))
                    ->visible(fn () => $this->record->received_from_courier_at === null && $this->record->courier_id && in_array($this->record->status, [
                        ShipmentStatus::DELIVERED,
                        ShipmentStatus::RETURNED_PAID,
                        ShipmentStatus::RETURNED_ON_MERCHANT,
                        ShipmentStatus::RETURNED_TO_BRANCH,
                    ], true)),
            ])
                ->label('الأوامر')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
