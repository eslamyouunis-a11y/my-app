<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use App\Enums\ShipmentRescheduleReason;
use App\Filament\Admin\Resources\ShipmentResource\Pages;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Courier;
use App\Models\Merchant;
use App\Models\Shipment;
use App\Services\PricingService;
use App\Services\ShipmentActionService;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction as TableCreateAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'الشحنات';
    protected static ?string $modelLabel = 'شحنة';
    protected static ?string $pluralModelLabel = 'الشحنات';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('الأطراف')
                ->icon('heroicon-m-users')
                ->schema([
                    Grid::make(2)->schema([
                        Grid::make(1)->schema([
                            Select::make('merchant_id')
                                ->label('التاجر')
                                ->relationship('merchant', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                    if ($state) {
                                        $merchant = Merchant::find($state);
                                        if ($merchant?->branch_id) {
                                            $set('branch_id', $merchant->branch_id);
                                        }
                                    }

                                    static::applyPricing($set, $get);
                                }),
                            Select::make('branch_id')
                                ->label('الفرع')
                                ->relationship('branch', 'name')
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                        ]),
                        Grid::make(1)->schema([
                            TextInput::make('receiver_name')
                                ->label('اسم المستلم')
                                ->required(),
                            TextInput::make('receiver_phone')
                                ->label('هاتف المستلم')
                                ->required()
                                ->extraInputAttributes(['dir' => 'ltr']),
                            TextInput::make('receiver_phone_alt')
                                ->label('هاتف بديل')
                                ->extraInputAttributes(['dir' => 'ltr']),
                        ]),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('governorate_id')
                            ->label('المحافظة')
                            ->relationship('governorate', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $set('area_id', null);
                                static::applyPricing($set, $get);
                            }),
                        Select::make('area_id')
                            ->label('المنطقة')
                            ->options(fn (Get $get) => Area::query()
                                ->where('governorate_id', $get('governorate_id'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Get $get): bool => ! $get('governorate_id'))
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                        Textarea::make('address')
                            ->label('عنوان المستلم')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                ]),
            Section::make('بيانات الشحنة')
                ->icon('heroicon-m-cube')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('shipping_type')
                            ->label('نوع الشحنة')
                            ->options(static::creationShippingTypeOptions())
                            ->default(ShipmentType::NORMAL)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                        TextInput::make('weight')
                            ->label('الوزن (كجم)')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                        Textarea::make('content')
                            ->label('محتوى الشحنة')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                    ]),
                    Textarea::make('returned_content')
                        ->label('محتوى المرتجع (جزئي/استبدال/مرتجع)')
                        ->rows(2)
                        ->columnSpanFull()
                        ->visible(function (Get $get): bool {
                            $type = $get('shipping_type');
                            $value = $type instanceof ShipmentType ? $type->value : (string) $type;
                            return in_array($value, [
                                ShipmentType::PARTIAL_DELIVERY->value,
                                ShipmentType::EXCHANGE->value,
                                ShipmentType::RETURN->value,
                            ], true);
                        })
                        ->required(function (Get $get): bool {
                            $type = $get('shipping_type');
                            $value = $type instanceof ShipmentType ? $type->value : (string) $type;
                            return in_array($value, [
                                ShipmentType::PARTIAL_DELIVERY->value,
                                ShipmentType::EXCHANGE->value,
                                ShipmentType::RETURN->value,
                            ], true);
                        }),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->columnSpanFull()
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                    Grid::make(3)->schema([
                        Toggle::make('is_open_allowed')
                            ->label('السماح بالفتح')
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                        Toggle::make('is_fragile')
                            ->label('قابل للكسر')
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                        Toggle::make('is_office_pickup')
                            ->label('استلام من المكتب')
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::applyPricing($set, $get)),
                    ]),
                ]),
            Section::make('البيانات المالية')
                ->icon('heroicon-m-calculator')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('order_price')
                            ->label('سعر الطلب (تحصيل عند التسليم)')
                            ->numeric()
                            ->default(0)
                            ->prefix('جنيه')
                            ->rule(function (Get $get): string {
                                $type = $get('shipping_type');
                                $value = $type instanceof ShipmentType ? $type->value : (string) $type;
                                return $value === ShipmentType::RETURN->value ? 'lt:0' : 'gte:0';
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::updateNetAmount($set, $get)),
                        TextInput::make('return_value')
                            ->label('قيمة المرتجع المدفوع')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('جنيه'),
                        TextInput::make('base_shipping_fee')
                            ->label('سعر الشحن الأساسي')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('جنيه'),
                        TextInput::make('extra_weight_fee')
                            ->label('رسوم الوزن الإضافي')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('جنيه'),
                        TextInput::make('total_shipping_fee')
                            ->label('إجمالي مصاريف الشحن')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('جنيه'),
                    ]),
                    Placeholder::make('pricing_summary')
                        ->label('ملخص مالي')
                        ->content(function (Get $get): string {
                            $cod = number_format((float) ($get('order_price') ?? 0), 0);
                            $total = number_format((float) ($get('total_shipping_fee') ?? 0), 0);
                            $net = number_format(((float) ($get('order_price') ?? 0)) - ((float) ($get('total_shipping_fee') ?? 0)), 0);

                            return "تحصيل من العميل: {$cod} جنيه | مصاريف الشحن: {$total} جنيه | صافي التاجر: {$net} جنيه";
                        }),
                    Hidden::make('merchant_net_amount')
                        ->dehydrated(),
                    Hidden::make('return_fee')
                        ->dehydrated(),
                    Hidden::make('cancellation_fee')
                        ->dehydrated(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $selectedRecords = fn ($livewire) => $livewire->getSelectedTableRecords();
        return $table
            ->columns([
                TextColumn::make('barcode')
                    ->label('الكود')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('merchant.name')
                    ->label('التاجر / المستلم')
                    ->description(fn (Shipment $record) => $record->receiver_name)
                    ->searchable(['merchant.name', 'receiver_name', 'receiver_phone']),
                TextColumn::make('governorate.name')
                    ->label('المحافظة / المنطقة')
                    ->description(fn (Shipment $record) => $record->area?->name)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('received_from_courier_at')
                    ->label('استلام من المندوب')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'تم الاستلام' : 'تم الاستلام')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
                TextColumn::make('total_shipping_fee')
                    ->label('إجمالي مصاريف الشحن')
                    ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                    ->alignEnd(),
                TextColumn::make('order_price')
                    ->label('تحصيل عند التسليم')
                    ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                    ->alignEnd(),
                TextColumn::make('courier.full_name')
                    ->label('المندوب')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
            ])
            ->selectable()
            ->recordUrl(fn (Shipment $record) => static::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->headerActions([
                TableCreateAction::make()
                    ->label('إضافة شحنة'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('select_one')
                        ->records($selectedRecords)
                        ->label('اختر شحنة واحدة')
                        ->disabled()
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records) === null),

                    BulkAction::make('accept')
                        ->records($selectedRecords)
                        ->label('قبول الشحنة')
                        ->icon('heroicon-m-check-circle')
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records)?->status === ShipmentStatus::SAVED)
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
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $user = auth()->user();
                            $branchId = $data['branch_id']
                                ?? $user?->branch_id
                                ?? $record->merchant?->branch_id;

                            app(ShipmentActionService::class)->handleAcceptance($record, $branchId);
                        }),

                    BulkAction::make('assign_courier')
                        ->records($selectedRecords)
                        ->label('تعيين مندوب')
                        ->icon('heroicon-m-user-plus')
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records)?->status === ShipmentStatus::ACCEPTED)
                        ->form(function (Collection $records): array {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return [];
                            }

                            return [
                                Select::make('courier_id')
                                    ->label('المندوب')
                                    ->options(fn () => Courier::query()
                                        ->where('branch_id', $record->branch_id)
                                        ->pluck('full_name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ];
                        })
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $record->update([
                                'courier_id' => $data['courier_id'],
                                'status' => ShipmentStatus::ASSIGNED,
                            ]);
                        }),

                    BulkAction::make('reschedule')
                        ->records($selectedRecords)
                        ->label('تأجيل')
                        ->icon('heroicon-m-clock')
                        ->visible(fn (?Collection $records): bool => in_array(static::getSingleSelected($records)?->status, [
                            ShipmentStatus::ACCEPTED,
                            ShipmentStatus::ASSIGNED,
                            ShipmentStatus::OUT_FOR_DELIVERY,
                        ], true))
                        ->form(function (Collection $records): array {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return [];
                            }

                            return [
                                DateTimePicker::make('rescheduled_for')
                                    ->label('تاريخ ووقت إعادة التسليم')
                                    ->seconds(false)
                                    ->required()
                                    ->rule(function () use ($record) {
                                        return function (string $attribute, $value, Closure $fail) use ($record): void {
                                            if (! $value) {
                                                return;
                                            }

                                            $currentDeliveryAt = $record->expected_delivery_at ?? $record->delivery_date;
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
                                    ->default($record->rescheduled_for ?? $record->expected_delivery_at ?? now()->addDay()),
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
                            ];
                        })
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            app(ShipmentActionService::class)->handleReschedule($record, $data);
                        }),

                    BulkAction::make('transfer_branch')
                        ->records($selectedRecords)
                        ->label('تحويل فرع')
                        ->icon('heroicon-m-arrow-path')
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records)?->status === ShipmentStatus::ACCEPTED)
                        ->form([
                            Select::make('branch_id')
                                ->label('الفرع')
                                ->options(Branch::query()->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $record->update([
                                'branch_id' => $data['branch_id'],
                                'courier_id' => null,
                                'status' => ShipmentStatus::ACCEPTED,
                            ]);
                        }),

                    BulkAction::make('cancel_return')
                        ->records($selectedRecords)
                        ->label('مرتجع إلغاء')
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn (?Collection $records): bool => in_array(static::getSingleSelected($records)?->status, [
                            ShipmentStatus::ACCEPTED,
                            ShipmentStatus::OUT_FOR_DELIVERY,
                            ShipmentStatus::RESCHEDULED,
                        ], true))
                        ->action(function (Collection $records): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $record->update([
                                'status' => ShipmentStatus::CANCELLED,
                            ]);
                        }),

                    BulkAction::make('handover_courier')
                        ->records($selectedRecords)
                        ->label('تسليم للمندوب')
                        ->icon('heroicon-m-truck')
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records)?->status === ShipmentStatus::ASSIGNED)
                        ->action(function (Collection $records): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $record->update([
                                'status' => ShipmentStatus::OUT_FOR_DELIVERY,
                            ]);
                        }),

                    BulkAction::make('change_courier')
                        ->records($selectedRecords)
                        ->label('تغيير مندوب')
                        ->icon('heroicon-m-user-plus')
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records)?->status === ShipmentStatus::ASSIGNED)
                        ->form(function (Collection $records): array {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return [];
                            }

                            return [
                                Select::make('courier_id')
                                    ->label('المندوب')
                                    ->options(fn () => Courier::query()
                                        ->where('branch_id', $record->branch_id)
                                        ->pluck('full_name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ];
                        })
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $record->update([
                                'courier_id' => $data['courier_id'],
                            ]);
                        }),

                    BulkAction::make('back_to_stock')
                        ->records($selectedRecords)
                        ->label('رجوع للمخزن')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->visible(fn (?Collection $records): bool => static::getSingleSelected($records)?->status === ShipmentStatus::ASSIGNED)
                        ->action(function (Collection $records): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            $record->update([
                                'courier_id' => null,
                                'status' => ShipmentStatus::ACCEPTED,
                            ]);
                        }),

                    BulkAction::make('deliver')
                        ->records($selectedRecords)
                        ->label('تسليم الشحنة')
                        ->icon('heroicon-m-check-badge')
                        ->visible(fn (?Collection $records): bool => in_array(static::getSingleSelected($records)?->status, [
                            ShipmentStatus::OUT_FOR_DELIVERY,
                            ShipmentStatus::RESCHEDULED,
                        ], true))
                        ->form(function (Collection $records): array {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return [];
                            }

                            $requiresReturnContent = in_array(
                                $record->shipping_type instanceof ShipmentType
                                    ? $record->shipping_type->value
                                    : (string) $record->shipping_type,
                                ['exchange', 'return', 'partial_delivery'],
                                true
                            );

                            return [
                                TextInput::make('cod_amount')
                                    ->label('تسليم الشحنة')
                                    ->numeric()
                                    ->required()
                                    ->default($record->order_price)
                                    ->rule(function () use ($record): string {
                                        $type = $record->shipping_type instanceof ShipmentType ? $record->shipping_type->value : (string) $record->shipping_type;
                                        return $type === ShipmentType::RETURN->value ? 'lt:0' : 'gte:0';
                                    })
                                    ->prefix('جنيه'),
                                Textarea::make('returned_content')
                                    ->label('تسليم الشحنة')
                                    ->default($record->returned_content)
                                    ->visible(fn () => $requiresReturnContent)
                                    ->required(fn () => $requiresReturnContent),
                            ];
                        })
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            app(ShipmentActionService::class)->handleDelivery($record, $data);
                        }),
                    BulkAction::make('return_paid')
                        ->records($selectedRecords)
                        ->label('مرتجع مدفوع')
                        ->icon('heroicon-m-currency-dollar')
                        ->visible(fn (?Collection $records): bool => in_array(static::getSingleSelected($records)?->status, [
                            ShipmentStatus::OUT_FOR_DELIVERY,
                            ShipmentStatus::RESCHEDULED,
                        ], true))
                        ->form(function (Collection $records): array {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return [];
                            }

                            return [
                                TextInput::make('return_value')
                                    ->label('قيمة المرتجع')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->required()
                                    ->default($record->return_value)
                                    ->prefix('جنيه'),
                            ];
                        })
                        ->action(function (Collection $records, array $data): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            app(ShipmentActionService::class)->handleReturnPaid($record, $data);
                        }),
                    BulkAction::make('return_on_merchant')
                        ->records($selectedRecords)
                        ->label('مرتجع على التاجر')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->visible(fn (?Collection $records): bool => in_array(static::getSingleSelected($records)?->status, [
                            ShipmentStatus::OUT_FOR_DELIVERY,
                            ShipmentStatus::RESCHEDULED,
                        ], true))
                        ->action(function (Collection $records): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            app(ShipmentActionService::class)->handleReturnOnMerchant($record);
                        }),
                    BulkAction::make('receive_from_courier')
                        ->records($selectedRecords)
                        ->label('استلام من المندوب')
                        ->icon('heroicon-m-inbox-arrow-down')
                        ->visible(function (?Collection $records): bool {
                            $record = static::getSingleSelected($records);
                            if (! $record || $record->received_from_courier_at || ! $record->courier_id) {
                                return false;
                            }
                            return in_array($record->status, [
                                ShipmentStatus::DELIVERED,
                                ShipmentStatus::RETURNED_PAID,
                                ShipmentStatus::RETURNED_ON_MERCHANT,
                                ShipmentStatus::RETURNED_TO_BRANCH,
                            ], true);
                        })
                        ->action(function (Collection $records): void {
                            $record = static::getSingleSelected($records);
                            if (! $record) {
                                return;
                            }

                            app(ShipmentActionService::class)->handleCourierReceipt($record, auth()->id());
                        }),
                ])
                    ->label('الأوامر')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button(),
            ]);
    }

    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageShipments::route('/'),
            'view' => Pages\ViewShipment::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('ملخص الشحنة')
                ->schema([
                    ViewEntry::make('barcode_summary')
                        ->hiddenLabel()
                        ->view('filament.admin.shipments.partials.barcode-summary')
                        ->columnSpanFull(),
                    TextEntry::make('status')
                        ->label('الحالة')
                        ->icon('heroicon-m-check-badge')
                        ->weight('bold')
                        ->badge(),
                    TextEntry::make('shipping_type')
                        ->label('نوع الشحنة')
                        ->icon('heroicon-m-cube')
                        ->weight('bold')
                        ->badge(),
                    TextEntry::make('merchant.name')
                        ->label('التاجر')
                        ->icon('heroicon-m-building-storefront')
                        ->weight('bold'),
                    TextEntry::make('receiver_name')
                        ->label('اسم المستلم')
                        ->icon('heroicon-m-user')
                        ->weight('bold'),
                    TextEntry::make('receiver_phone')
                        ->label('هاتف المستلم')
                        ->icon('heroicon-m-phone')
                        ->weight('bold'),
                    TextEntry::make('address')
                        ->label('عنوان المستلم')
                        ->icon('heroicon-m-map-pin')
                        ->weight('bold'),
                    TextEntry::make('returned_content')
                        ->label('تسليم الشحنة')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->weight('bold')
                        ->visible(fn ($record) => filled($record?->returned_content)),
                ])->columns(3),
            InfolistSection::make('مواعيد التسليم والتأجيل')
                ->schema([
                    TextEntry::make('created_at')
                        ->label('تاريخ الإنشاء')
                        ->icon('heroicon-m-calendar-days')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i'),
                    TextEntry::make('accepted_at')
                        ->label('تاريخ القبول')
                        ->icon('heroicon-m-check-circle')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i')
                        ->placeholder('-'),
                    TextEntry::make('expected_delivery_at')
                        ->label('ميعاد التسليم المتوقع')
                        ->icon('heroicon-m-clock')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i')
                        ->placeholder('-'),
                    TextEntry::make('delivered_at')
                        ->label('وقت التسليم')
                        ->icon('heroicon-m-calendar-days')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i')
                        ->placeholder('-'),
                    TextEntry::make('received_from_courier_at')
                        ->label('استلام من المندوب')
                        ->icon('heroicon-m-inbox-arrow-down')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i')
                        ->placeholder('-'),
                    TextEntry::make('rescheduled_at')
                        ->label('تاريخ التأجيل')
                        ->icon('heroicon-m-arrow-path')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i')
                        ->placeholder('-'),
                    TextEntry::make('rescheduled_for')
                        ->label('إعادة التسليم في')
                        ->icon('heroicon-m-clock')
                        ->weight('bold')
                        ->dateTime('Y-m-d H:i')
                        ->placeholder('-'),
                    TextEntry::make('reschedule_reason')
                        ->label('سبب التأجيل')
                        ->icon('heroicon-m-information-circle')
                        ->weight('bold')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '-'),
                    TextEntry::make('reschedule_notes')
                        ->label('تفاصيل التأجيل')
                        ->icon('heroicon-m-document-text')
                        ->weight('bold')
                        ->placeholder('-'),
                ])->columns(3),
            InfolistSection::make('البيانات المالية')
                ->schema([
                    TextEntry::make('order_price')
                        ->label('سعر الطلب (تحصيل عند التسليم)')
                        ->icon('heroicon-m-banknotes')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')
                        ->suffix(' جنيه'),
                    TextEntry::make('total_shipping_fee')
                        ->label('إجمالي مصاريف الشحن')
                        ->icon('heroicon-m-truck')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')
                        ->suffix(' جنيه'),
                    TextEntry::make('return_value')
                        ->label('قيمة المرتجع')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')
                        ->suffix(' جنيه'),
                    TextEntry::make('cod_amount')
                        ->label('المبلغ المحصل')
                        ->icon('heroicon-m-wallet')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')
                        ->suffix(' جنيه'),
                ])->columns(4),
        ]);
    }

    private static function creationShippingTypeOptions(): array
    {
        return [
            ShipmentType::NORMAL->value => ShipmentType::NORMAL->getLabel(),
            ShipmentType::PARTIAL_DELIVERY->value => ShipmentType::PARTIAL_DELIVERY->getLabel(),
            ShipmentType::EXCHANGE->value => ShipmentType::EXCHANGE->getLabel(),
            ShipmentType::RETURN->value => ShipmentType::RETURN->getLabel(),
        ];
    }

    private static function applyPricing(Set $set, Get $get): void
    {
        $merchantId = $get('merchant_id');
        $governorateId = $get('governorate_id');

        if (! $merchantId || ! $governorateId) {
            static::resetPricing($set);
            static::updateNetAmount($set, $get);
            return;
        }

        $merchant = Merchant::find($merchantId);
        if (! $merchant) {
            static::resetPricing($set);
            static::updateNetAmount($set, $get);
            return;
        }

        $pricing = app(PricingService::class)->calculate(
            $merchant,
            (int) $governorateId,
            $get('area_id'),
            (float) ($get('weight') ?: 1),
            (bool) ($get('is_office_pickup') ?? false)
        );

        $set('base_shipping_fee', $pricing['base_fee']);
        $set('extra_weight_fee', $pricing['extra_weight_fee']);
        $set('total_shipping_fee', $pricing['total_shipping_fee']);
        $set('return_fee', $pricing['return_fee']);
        $set('cancellation_fee', $pricing['cancellation_fee']);

        static::updateNetAmount($set, $get);
    }

    private static function resetPricing(Set $set): void
    {
        $set('base_shipping_fee', 0);
        $set('extra_weight_fee', 0);
        $set('total_shipping_fee', 0);
        $set('return_fee', 0);
        $set('cancellation_fee', 0);
    }

    private static function updateNetAmount(Set $set, Get $get): void
    {
        $orderPrice = (float) ($get('order_price') ?? 0);
        $totalShipping = (float) ($get('total_shipping_fee') ?? 0);
        $set('merchant_net_amount', round($orderPrice - $totalShipping, 2));
    }

    private static function getSingleSelected(?Collection $records): ?Shipment
    {
        if (! $records || $records->count() !== 1) {
            return null;
        }

        $record = $records->first();
        if ($record instanceof Shipment) {
            return $record;
        }

        $recordId = null;
        if (is_scalar($record)) {
            $recordId = $record;
        } elseif (is_array($record) && array_key_exists('id', $record)) {
            $recordId = $record['id'];
        }

        if ($recordId === null) {
            return null;
        }

        static $cachedId = null;
        static $cachedRecord = null;

        if ($cachedId !== null && (string) $cachedId === (string) $recordId && $cachedRecord instanceof Shipment) {
            return $cachedRecord;
        }

        $cachedId = $recordId;
        $cachedRecord = Shipment::query()->whereKey($recordId)->first();

        return $cachedRecord;
    }
}
