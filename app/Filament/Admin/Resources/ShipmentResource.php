<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use App\Models\Merchant;
use App\Models\Branch;
use App\Models\ShippingFee;
use App\Models\ShippingZoneFee;
use App\Enums\ShipmentStatus;
use App\Enums\ShipmentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
// ✅ استدعاءات Infolist
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'الشحنات';
    protected static ?string $modelLabel = 'شحنة';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('أطراف الشحنة')
                ->icon('heroicon-m-users')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Group::make([
                            Select::make('merchant_id')
                                ->label('الراسل (التاجر)')
                                ->options(Merchant::pluck('name', 'id'))
                                ->searchable()->preload()->required()->live()
                                ->prefixIcon('heroicon-m-building-storefront')
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        $merchant = Merchant::find($state);
                                        $set('branch_id', $merchant?->branch_id);
                                    }
                                    self::calculateShippingFees($set, $state, null, null, null, false);
                                }),
                            Select::make('branch_id')->label('الفرع المسؤول')->relationship('branch', 'name')->disabled()->dehydrated()->required()->prefixIcon('heroicon-m-building-office-2'),
                        ]),
                        Forms\Components\Group::make([
                            TextInput::make('receiver_name')->label('اسم المرسل إليه')->required()->prefixIcon('heroicon-m-user'),
                            TextInput::make('receiver_phone')->label('رقم الهاتف')->tel()->required()->prefixIcon('heroicon-m-phone'),
                            TextInput::make('receiver_phone_alt')->label('رقم هاتف بديل')->tel()->prefixIcon('heroicon-m-device-phone-mobile'),
                        ]),
                    ]),
                    Forms\Components\fieldset::make('العنوان الجغرافي')->schema([
                        Grid::make(3)->schema([
                            Select::make('governorate_id')->label('المحافظة')->relationship('governorate', 'name')->searchable()->preload()->required()->live()->prefixIcon('heroicon-m-map')
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    $set('area_id', null);
                                    self::calculateShippingFees($set, $get('merchant_id'), $state, null, $get('weight'), $get('is_office_pickup'));
                                }),
                            Select::make('area_id')->label('المنطقة')->relationship('area', 'name', fn ($query, Get $get) => $query->where('governorate_id', $get('governorate_id')))->searchable()->preload()->required()->disabled(fn (Get $get) => ! $get('governorate_id'))->live()->prefixIcon('heroicon-m-map-pin')
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    self::calculateShippingFees($set, $get('merchant_id'), $get('governorate_id'), $state, $get('weight'), $get('is_office_pickup'));
                                }),
                            TextInput::make('address')->label('العنوان بالتفصيل')->prefixIcon('heroicon-m-home'),
                        ]),
                    ]),
                ]),
            Section::make('تفاصيل الطرد')->icon('heroicon-m-cube')->schema([
                Grid::make(3)->schema([
                    Select::make('shipping_type')->label('نوع الشحنة')->options(ShipmentType::class)->default('normal')->live()->required()->prefixIcon('heroicon-m-tag'),
                    TextInput::make('weight')->label('الوزن (كجم)')->numeric()->default(1)->minValue(1)->suffix('كجم')->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            self::calculateShippingFees($set, $get('merchant_id'), $get('governorate_id'), $get('area_id'), $state, $get('is_office_pickup'));
                        }),
                    TextInput::make('content')->label('محتوى الشحنة')->required()->prefixIcon('heroicon-m-document-text'),
                ]),
                TextInput::make('returned_content')->label('المحتوى المرتجع')->visible(fn (Get $get) => in_array($get('shipping_type'), ['exchange', 'return_pickup']))->columnSpanFull()->prefixIcon('heroicon-m-arrow-path'),
                Grid::make(4)->schema([
                    Toggle::make('is_open_allowed')->label('مسموح الفتح')->onColor('success')->offColor('danger'),
                    Toggle::make('is_fragile')->label('قابل للكسر')->onColor('warning')->offColor('gray'),
                    Toggle::make('is_office_pickup')->label('استلام من المكتب')->onColor('info')->offColor('gray')->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            self::calculateShippingFees($set, $get('merchant_id'), $get('governorate_id'), $get('area_id'), $get('weight'), $state);
                        }),
                ]),
            ]),
            Section::make('الماليات والحسابات')->icon('heroicon-m-calculator')->schema([
                Grid::make(3)->schema([
                    TextInput::make('order_price')->label('سعر المنتج (COD)')->numeric()->default(0)->prefix('ج.م')->live(onBlur: true)->afterStateUpdated(fn (Set $set, Get $get, $state) => $set('merchant_net_amount', (float)$state - (float)$get('total_shipping_fee')))->extraInputAttributes(['style' => 'font-size: 1.2rem; font-weight: bold; color: green;']),
                    TextInput::make('total_shipping_fee')->label('إجمالي الشحن')->numeric()->disabled()->dehydrated()->prefix('ج.م')->extraInputAttributes(['style' => 'font-weight: bold;']),
                    TextInput::make('merchant_net_amount')->label('الصافي للتاجر')->numeric()->disabled()->dehydrated()->prefix('ج.م')->extraInputAttributes(['style' => 'font-weight: bold; color: blue;']),
                ]),
                Section::make('تفاصيل الرسوم')->collapsed()->compact()->schema([
                    Grid::make(4)->schema([
                        TextInput::make('base_shipping_fee')->label('شحن أساسي')->disabled()->dehydrated(),
                        TextInput::make('extra_weight_fee')->label('وزن زائد')->disabled()->dehydrated(),
                        TextInput::make('return_fee')->label('رسوم المرتجع')->disabled()->dehydrated(),
                        TextInput::make('cancellation_fee')->label('رسوم الإلغاء')->disabled()->dehydrated(),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ✅ 1. كود الشحنة (أول عمود ومميز)
                Tables\Columns\TextColumn::make('barcode')
                    ->label('كود الشحنة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->icon('heroicon-m-qr-code'),

                Tables\Columns\TextColumn::make('merchant.name')
                    ->label('التاجر')
                    ->searchable()
                    ->description(fn (Shipment $record) => $record->merchant->merchant_code),

                Tables\Columns\TextColumn::make('receiver_name')
                    ->label('العميل')
                    ->searchable()
                    ->description(fn (Shipment $record) => $record->receiver_phone),

                Tables\Columns\TextColumn::make('governorate.name')
                    ->label('المحافظة')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),

                Tables\Columns\TextColumn::make('total_shipping_fee')
                    ->label('شحن')
                    ->money('EGP')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('order_price')
                    ->label('COD')
                    ->money('EGP')
                    ->weight('bold')
                    ->color('success')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ')
                    ->date('d/m/Y')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ✅ صفحة العرض (View) المميزة
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // === 1. الهيدر (كود الشحنة والحالة) ===
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            // ✅ كود الشحنة ظاهر بوضوح
                            Infolists\Components\TextEntry::make('barcode')
                                ->label('كود الشحنة')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->copyable()
                                ->icon('heroicon-m-qr-code')
                                ->color('primary'),

                            Infolists\Components\TextEntry::make('status')
                                ->label('الحالة الحالية')
                                ->badge()
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                            Infolists\Components\TextEntry::make('shipping_type')
                                ->label('نوع الشحنة')
                                ->badge()
                                ->color('info'),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('تاريخ الإنشاء')
                                ->date('d/m/Y h:i A')
                                ->icon('heroicon-m-calendar'),
                        ]),
                    ]),

                // === 2. التفاصيل المالية (أهم حاجة) ===
                Infolists\Components\Section::make('التفاصيل المالية')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('order_price')
                                ->label('المبلغ المطلوب تحصيله (COD)')
                                ->money('EGP')
                                ->color('success')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                            Infolists\Components\TextEntry::make('total_shipping_fee')
                                ->label('تكلفة الشحن')
                                ->money('EGP')
                                ->color('danger'),

                            Infolists\Components\TextEntry::make('merchant_net_amount')
                                ->label('صافي مستحقات التاجر')
                                ->money('EGP')
                                ->color('primary')
                                ->weight('bold'),
                        ]),

                        // تفاصيل الرسوم الإضافية
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('base_shipping_fee')->label('شحن أساسي')->money('EGP'),
                            Infolists\Components\TextEntry::make('extra_weight_fee')->label('وزن زائد')->money('EGP'),
                            Infolists\Components\TextEntry::make('return_fee')->label('غرامة المرتجع')->money('EGP'),
                            Infolists\Components\TextEntry::make('cancellation_fee')->label('غرامة الإلغاء')->money('EGP'),
                        ])->visible(fn ($record) => $record->extra_weight_fee > 0 || $record->return_fee > 0),
                    ]),

                // === 3. الأطراف والتوصيل ===
                Infolists\Components\Grid::make(2)->schema([
                    // بيانات الراسل
                    Infolists\Components\Section::make('بيانات الراسل')
                        ->icon('heroicon-m-building-storefront')
                        ->schema([
                            Infolists\Components\TextEntry::make('merchant.name')->label('المتجر'),
                            Infolists\Components\TextEntry::make('merchant.merchant_code')->label('كود التاجر')->badge(),
                            Infolists\Components\TextEntry::make('branch.name')->label('الفرع المسؤول')->badge()->color('warning'),
                        ])->columnSpan(1),

                    // بيانات العميل
                    Infolists\Components\Section::make('بيانات المرسل إليه')
                        ->icon('heroicon-m-user')
                        ->schema([
                            Infolists\Components\TextEntry::make('receiver_name')->label('الاسم'),
                            Infolists\Components\TextEntry::make('receiver_phone')->label('الهاتف')->icon('heroicon-m-phone')->url(fn ($record) => "tel:{$record->receiver_phone}"),
                            Infolists\Components\TextEntry::make('address')
                                ->label('العنوان')
                                ->formatStateUsing(fn ($state, $record) => "{$record->governorate->name} - {$record->area?->name} - {$state}"),
                        ])->columnSpan(1),
                ]),

                // === 4. تفاصيل الطرد ===
                Infolists\Components\Section::make('مواصفات الطرد')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('content')->label('المحتوى'),
                            Infolists\Components\TextEntry::make('weight')->label('الوزن')->suffix(' كجم'),
                            Infolists\Components\TextEntry::make('is_open_allowed')->label('مسموح الفتح')->badge()->color(fn ($state) => $state ? 'success' : 'gray')->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا'),
                            Infolists\Components\TextEntry::make('is_fragile')->label('قابل للكسر')->badge()->color(fn ($state) => $state ? 'warning' : 'gray')->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا'),
                        ]),
                    ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
            'view' => Pages\ViewShipment::route('/{record}'),
        ];
    }

    protected static function calculateShippingFees(Set $set, $merchantId, $governorateId, $areaId, $weight, $isOfficePickup)
    {
        $set('base_shipping_fee', 0); $set('extra_weight_fee', 0); $set('total_shipping_fee', 0);
        $set('return_fee', 0); $set('cancellation_fee', 0);

        if (!$merchantId || !$governorateId) return;

        $merchant = Merchant::find($merchantId);
        if (!$merchant) return;
        $branch = $merchant->branch;
        if (!$branch) return;

        $fromGovId = $branch->governorate_id;
        $toGovId = $governorateId;

        $baseFee = 0; $foundPrice = false;

        $specialPrice = $merchant->specialPrices()->where('governorate_id', $toGovId)->when($areaId, fn ($q) => $q->where('area_id', $areaId))->first();
        if ($specialPrice) {
            $baseFee = $isOfficePickup ? $specialPrice->office_delivery_fee : $specialPrice->delivery_fee;
            $foundPrice = true;
        }

        if (!$foundPrice) {
            $govSpecialPrice = $merchant->specialPrices()->where('governorate_id', $toGovId)->whereNull('area_id')->first();
            if ($govSpecialPrice) {
                $baseFee = $isOfficePickup ? $govSpecialPrice->office_delivery_fee : $govSpecialPrice->delivery_fee;
                $foundPrice = true;
            }
        }

        if (!$foundPrice) {
            $shippingFee = ShippingFee::where('from_governorate_id', $fromGovId)->where('to_governorate_id', $toGovId)->first();
            if ($shippingFee) {
                $zoneFee = $areaId ? ShippingZoneFee::where('shipping_fee_id', $shippingFee->id)->where('area_id', $areaId)->first() : null;
                $baseFee = $zoneFee ? ($isOfficePickup ? $zoneFee->office_price : $zoneFee->home_price) : ($isOfficePickup ? $shippingFee->office_price : $shippingFee->home_price);
            }
        }

        $extraWeightFee = 0;
        $weightLimit = 1;
        if ($weight > $weightLimit) {
            $extraKilos = ceil($weight - $weightLimit);
            $pricePerKilo = $merchant->extra_weight_price ?? 0;
            $extraWeightFee = $extraKilos * $pricePerKilo;
        }

        $returnFee = $merchant->paid_return_fee > 0 ? $merchant->paid_return_fee : ($branch->normal_paid_return_fee ?? 0);
        $cancellationFee = $merchant->cancellation_fee > 0 ? $merchant->cancellation_fee : 0;

        $totalShipping = $baseFee + $extraWeightFee;

        $set('base_shipping_fee', number_format($baseFee, 2, '.', ''));
        $set('extra_weight_fee', number_format($extraWeightFee, 2, '.', ''));
        $set('total_shipping_fee', number_format($totalShipping, 2, '.', ''));
        $set('return_fee', number_format($returnFee, 2, '.', ''));
        $set('cancellation_fee', number_format($cancellationFee, 2, '.', ''));
    }
}
