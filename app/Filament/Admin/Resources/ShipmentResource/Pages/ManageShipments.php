<?php

namespace App\Filament\Admin\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Admin\Resources\ShipmentResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageShipments extends ManageRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function updatedSelectedTableRecords(): void
    {
        if (property_exists($this, 'cachedSelectedTableRecords')) {
            unset($this->cachedSelectedTableRecords);
        }
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'saved' => Tab::make('محفوظة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShipmentStatus::SAVED)),
            'accepted' => Tab::make('في المخزن')
                ->icon('heroicon-m-eye-slash')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', ShipmentStatus::ACCEPTED)
                    ->where(function (Builder $query): void {
                        $query
                            ->whereDate('delivery_date', '<=', today())
                            ->orWhereNull('delivery_date');
                    })),
            'accepted_postponed' => Tab::make('عرض المؤجلة')
                ->icon('heroicon-m-eye')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', ShipmentStatus::ACCEPTED)
                    ->whereDate('delivery_date', '>', today())),
            'assigned' => Tab::make('مسندة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShipmentStatus::ASSIGNED)),
            'with_courier' => Tab::make('مع المندوب')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ShipmentStatus::OUT_FOR_DELIVERY,
                    ShipmentStatus::RESCHEDULED,
                ])),
            'delivered' => Tab::make('تم التسليم')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShipmentStatus::DELIVERED)),
            'pending_receipt' => Tab::make('بانتظار الاستلام')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNull('received_from_courier_at')
                    ->whereNotNull('courier_id')
                    ->whereIn('status', [
                        ShipmentStatus::DELIVERED,
                        ShipmentStatus::RETURNED_PAID,
                        ShipmentStatus::RETURNED_ON_MERCHANT,
                        ShipmentStatus::RETURNED_TO_BRANCH,
                    ])),
            'returns' => Tab::make('مرتجعات')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ShipmentStatus::RETURNED_PAID,
                    ShipmentStatus::RETURNED_ON_MERCHANT,
                    ShipmentStatus::CANCELLED,
                    ShipmentStatus::RETURNED_TO_BRANCH,
                    ShipmentStatus::RETURNED_TO_MERCHANT,
                ])),
        ];
    }
}
