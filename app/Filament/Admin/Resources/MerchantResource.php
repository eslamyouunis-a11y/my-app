<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MerchantResource\Pages;
use App\Models\Merchant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Illuminate\Validation\Rule;
// ✅ استدعاءات Infolist
use Filament\Infolists;
use Filament\Infolists\Infolist;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'التجار';
    protected static ?string $modelLabel = 'تاجر';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('MerchantData')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('الملف الشخصي')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('name')->label('اسم المتجر/الشركة')->required(),
                                    Forms\Components\Select::make('branch_id')->label('الفرع التابع له')->relationship('branch', 'name')->required()->searchable()->preload(),
                                    Forms\Components\Select::make('governorate_id')->label('المحافظة')->relationship('governorate', 'name')->live()->afterStateUpdated(fn ($set) => $set('area_id', null))->searchable()->preload(),
                                    Forms\Components\Select::make('area_id')->label('المنطقة')->relationship('area', 'name', fn ($query, $get) => $query->where('governorate_id', $get('governorate_id')))->disabled(fn ($get) => !$get('governorate_id'))->searchable()->preload(),
                                    TextInput::make('address')->label('العنوان التفصيلي')->columnSpanFull(),
                                ])->columns(2),
                            Section::make('المسؤولين')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Forms\Components\Group::make([
                                            TextInput::make('contact_person_name')->label('اسم المسؤول')->required(),
                                            TextInput::make('contact_person_phone')->label('رقم المسؤول')->tel()->required(),
                                        ]),
                                        Forms\Components\Group::make([
                                            TextInput::make('follow_up_name')->label('مسؤول المتابعة'),
                                            TextInput::make('follow_up_phone')->label('رقم المتابعة')->tel(),
                                        ]),
                                    ]),
                                ]),
                        ]),
                    Forms\Components\Tabs\Tab::make('بيانات الدخول')
                        ->icon('heroicon-m-key')
                        ->schema([
                            TextInput::make('email')->label('البريد الإلكتروني')->email()->required()
                                ->rule(function ($record) {
                                    $userId = $record?->user?->id;
                                    return Rule::unique('users', 'email')->ignore($userId);
                                }),
                            TextInput::make('password')->label('كلمة المرور')->password()->revealable()->required(fn ($livewire) => $livewire instanceof Pages\CreateMerchant)->helperText('اتركه فارغاً عند التعديل.'),
                        ]),
                    Forms\Components\Tabs\Tab::make('المالية والأسعار')
                        ->icon('heroicon-m-currency-dollar')
                        ->schema([
                            Section::make('تخصيص الرسوم الافتراضية')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('extra_weight_price')->label('سعر الكيلو الزيادة')->numeric()->suffix('جنيه'),
                                        Forms\Components\Group::make([
                                            TextInput::make('cancellation_fee')->label('رسوم الإلغاء (قيمة)')->numeric(),
                                            TextInput::make('cancellation_percent')->label('رسوم الإلغاء (%)')->numeric(),
                                        ])->label('الإلغاء'),
                                        Forms\Components\Group::make([
                                            TextInput::make('paid_return_fee')->label('مرتجع مدفوع (قيمة)')->numeric(),
                                            TextInput::make('paid_return_percent')->label('مرتجع مدفوع (%)')->numeric(),
                                        ])->label('مرتجع مدفوع'),
                                        Forms\Components\Group::make([
                                            TextInput::make('return_on_sender_fee')->label('مرتجع راسل (قيمة)')->numeric(),
                                            TextInput::make('return_on_sender_percent')->label('مرتجع راسل (%)')->numeric(),
                                        ])->label('مرتجع على الراسل'),
                                    ]),
                                ]),
                            Repeater::make('specialPrices')
                                ->label('قائمة مصاريف الشحن الخاصة')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Group::make([
                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('governorate_id')->label('المحافظة')->relationship('governorate', 'name')->live()->afterStateUpdated(fn ($set) => $set('area_id', null))->required()->searchable()->preload(),
                                            Forms\Components\Select::make('area_id')->label('المنطقة')->relationship('area', 'name', fn ($query, $get) => $query->where('governorate_id', $get('governorate_id')))->disabled(fn ($get) => !$get('governorate_id'))->searchable()->preload()->helperText('اختياري: اتركه فارغاً لتطبيق السعر على المحافظة بالكامل'),
                                        ]),
                                        Grid::make(2)->schema([
                                            TextInput::make('delivery_fee')->label('توصيل للمنزل')->numeric()->required()->suffix('جنيه'),
                                            TextInput::make('office_delivery_fee')->label('توصيل للمكتب')->numeric()->required()->suffix('جنيه')->default(0),
                                        ]),
                                    ])->columnSpanFull(),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('إضافة سعر خاص')
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => \App\Models\Governorate::find($state['governorate_id'] ?? null)?->name),
                        ]),
                    Forms\Components\Tabs\Tab::make('المنتجات')
                        ->icon('heroicon-m-shopping-bag')
                        ->schema([
                            Repeater::make('products')
                                ->label('قائمة المنتجات')
                                ->relationship()
                                ->schema([
                                    TextInput::make('name')->label('اسم المنتج')->required(),
                                    TextInput::make('sku')->label('كود المنتج'),
                                    TextInput::make('default_weight')->label('الوزن (كجم)')->numeric()->default(1),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel('إضافة منتج جديد'),
                        ]),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('merchant_code')->label('الكود')->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('name')->label('المتجر')->searchable(),
                Tables\Columns\TextColumn::make('branch.name')->label('الفرع')->badge(),
                Tables\Columns\TextColumn::make('contact_person_name')->label('المسؤول'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')->relationship('branch', 'name')->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // ✅
                Tables\Actions\EditAction::make(),
            ]);
    }

    // ✅ تصميم صفحة العرض
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. كارت الرصيد
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->label('اسم المتجر')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                            Infolists\Components\TextEntry::make('merchant_code')
                                ->label('كود التاجر')
                                ->copyable()
                                ->badge(),

                            Infolists\Components\TextEntry::make('balance')
                                ->label('الرصيد الحالي')
                                ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->color('primary')
                                ->icon('heroicon-m-wallet'),
                        ]),
                    ]),

                // 2. التابات
                Infolists\Components\Tabs::make('MerchantDetails')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('بيانات التواصل')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Infolists\Components\Grid::make(2)->schema([
                                    Infolists\Components\TextEntry::make('contact_person_name')->label('المسؤول'),
                                    Infolists\Components\TextEntry::make('contact_person_phone')->label('هاتف المسؤول')->icon('heroicon-m-phone'),
                                    Infolists\Components\TextEntry::make('email')->label('البريد الإلكتروني')->icon('heroicon-m-envelope'),
                                    Infolists\Components\TextEntry::make('branch.name')->label('الفرع المسؤول'),
                                    Infolists\Components\TextEntry::make('address')->label('العنوان')->columnSpanFull(),
                                ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make('إعدادات الرسوم')
                            ->icon('heroicon-m-calculator')
                            ->schema([
                                Infolists\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('extra_weight_price')->label('سعر الكيلو الزيادة')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                    Infolists\Components\TextEntry::make('cancellation_fee')->label('رسوم الإلغاء')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                    Infolists\Components\TextEntry::make('paid_return_fee')->label('رسوم المرتجع')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make('أسعار الشحن الخاصة')
                            ->icon('heroicon-m-map')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('specialPrices')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)->schema([
                                            Infolists\Components\TextEntry::make('governorate.name')->label('المحافظة')->badge(),
                                            Infolists\Components\TextEntry::make('delivery_fee')->label('توصيل منزل')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                            Infolists\Components\TextEntry::make('office_delivery_fee')->label('توصيل مكتب')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                        ]),
                                    ])
                                    ->grid(2),
                            ]),
                        Infolists\Components\Tabs\Tab::make('المنتجات')
                            ->icon('heroicon-m-shopping-bag')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('products')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)->schema([
                                            Infolists\Components\TextEntry::make('name')->label('المنتج'),
                                            Infolists\Components\TextEntry::make('sku')->label('كود المنتج'),
                                            Infolists\Components\TextEntry::make('default_weight')->label('الوزن'),
                                        ]),
                                    ])
                                    ->grid(2),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
            'view' => Pages\ViewMerchant::route('/{record}'), // ✅
        ];
    }
}
