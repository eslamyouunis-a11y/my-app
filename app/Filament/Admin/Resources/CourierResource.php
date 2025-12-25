<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CourierResource\Pages;
use App\Models\Courier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// ✅ استدعاءات الـ Infolist
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'المناديب';
    protected static ?string $modelLabel = 'مندوب';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('البيانات الشخصية')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('full_name')->label('الاسم بالكامل')->required(),
                                    TextInput::make('phone')->label('رقم الهاتف')->tel()->required()->unique(ignoreRecord: true),
                                    TextInput::make('national_id')->label('الرقم القومي')->numeric()->length(14)->unique(ignoreRecord: true),
                                    Forms\Components\DatePicker::make('birth_date')->label('تاريخ الميلاد'),
                                    Forms\Components\Select::make('branch_id')->label('الفرع التابع له')->relationship('branch', 'name')->searchable()->preload()->required(),
                                    Forms\Components\Select::make('governorate_id')->label('المحافظة')->relationship('governorate', 'name')->searchable()->live()->preload()->afterStateUpdated(fn (Forms\Set $set) => $set('area_id', null)),
                                    Forms\Components\Select::make('area_id')->label('المنطقة')->relationship('area', 'name', modifyQueryUsing: function ($query, Forms\Get $get) {
                                            return $query->where('governorate_id', $get('governorate_id'));
                                        })->searchable()->preload()->disabled(fn (Forms\Get $get) => ! $get('governorate_id')),
                                    Forms\Components\Textarea::make('address')->label('العنوان')->columnSpanFull(),
                                ])->columns(2),
                        ]),
                    Forms\Components\Tabs\Tab::make('بيانات الدخول')
                        ->icon('heroicon-m-key')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique('users', 'email', ignoreRecord: true),
                                    TextInput::make('password')->label('كلمة المرور')->password()->revealable()->required(fn ($livewire) => $livewire instanceof Pages\CreateCourier)->helperText('اتركه فارغاً عند التعديل.'),
                                ])->columns(2),
                        ]),
                    Forms\Components\Tabs\Tab::make('المركبة')
                        ->schema([
                            Forms\Components\Select::make('vehicle_type')->label('نوع المركبة')->options(['motorcycle' => 'موتوسيكل', 'car' => 'سيارة', 'van' => 'فان', 'bicycle' => 'دراجة']),
                            Forms\Components\DatePicker::make('driving_license_expiry')->label('انتهاء رخصة القيادة'),
                            Forms\Components\DatePicker::make('vehicle_license_expiry')->label('انتهاء رخصة المركبة'),
                        ])->columns(3),
                    Forms\Components\Tabs\Tab::make('العمولات')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                             Forms\Components\Section::make('هيكلة عمولات المندوب')
                                ->relationship('commission')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Forms\Components\Group::make([
                                            TextInput::make('delivery_value')->label('قيمة التسليم')->numeric()->suffix('جنيه'),
                                            TextInput::make('delivery_percentage')->label('نسبة التسليم')->numeric()->suffix('%'),
                                        ])->label('تسليم ناجح'),
                                        Forms\Components\Group::make([
                                            TextInput::make('paid_value')->label('قيمة مرتجع مدفوع')->numeric()->suffix('جنيه'),
                                            TextInput::make('paid_percentage')->label('نسبة مرتجع مدفوع')->numeric()->suffix('%'),
                                        ])->label('مرتجع مدفوع'),
                                        Forms\Components\Group::make([
                                            TextInput::make('sender_return_value')->label('قيمة مرتجع راسل')->numeric()->suffix('جنيه'),
                                            TextInput::make('sender_return_percentage')->label('نسبة مرتجع راسل')->numeric()->suffix('%'),
                                        ])->label('مرتجع على الراسل'),
                                    ]),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('courier_code')->label('الكود')->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('full_name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف'),
                Tables\Columns\TextColumn::make('branch.name')->label('الفرع')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')->relationship('branch', 'name')->label('الفرع')->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // ✅ زر العرض
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    // ✅ تصميم صفحة العرض (Infolist)
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. قسم الأرصدة
                Infolists\Components\Section::make('المحفظة والأرصدة')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('commission_balance')
                                ->label('رصيد العمولة (للمندوب)')
                                ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->color('success')
                                ->icon('heroicon-m-banknotes'),

                            Infolists\Components\TextEntry::make('custody_balance')
                                ->label('رصيد العهدة (على المندوب)')
                                ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->color('danger')
                                ->icon('heroicon-m-archive-box'),

                            Infolists\Components\TextEntry::make('is_active')
                                ->label('حالة الحساب')
                                ->badge()
                                ->formatStateUsing(fn (bool $state) => $state ? 'نشط' : 'متوقف')
                                ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                        ]),
                    ]),

                // 2. التفاصيل
                Infolists\Components\Tabs::make('Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('البيانات الشخصية')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Infolists\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('full_name')->label('الاسم'),
                                    Infolists\Components\TextEntry::make('courier_code')->label('الكود')->copyable(),
                                    Infolists\Components\TextEntry::make('phone')->label('الهاتف')->icon('heroicon-m-phone'),
                                    Infolists\Components\TextEntry::make('national_id')->label('الرقم القومي'),
                                    Infolists\Components\TextEntry::make('birth_date')->label('تاريخ الميلاد')->date(),
                                    Infolists\Components\TextEntry::make('branch.name')->label('الفرع التابع له'),
                                    Infolists\Components\TextEntry::make('address')->label('العنوان')->columnSpanFull(),
                                ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make('المركبة')
                            ->icon('heroicon-m-truck')
                            ->schema([
                                Infolists\Components\Grid::make(3)->schema([
                                    Infolists\Components\TextEntry::make('vehicle_type')->label('نوع المركبة')->badge(),
                                    Infolists\Components\TextEntry::make('driving_license_expiry')->label('انتهاء رخصة القيادة')->date(),
                                    Infolists\Components\TextEntry::make('vehicle_license_expiry')->label('انتهاء رخصة المركبة')->date(),
                                ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make('العمولات المتفق عليها')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Infolists\Components\Section::make('التسليم والمرتجعات')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)->schema([
                                            Infolists\Components\TextEntry::make('commission.delivery_value')->label('قيمة التسليم')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')->placeholder('الافتراضي'),
                                            Infolists\Components\TextEntry::make('commission.paid_value')->label('قيمة المرتجع')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')->placeholder('الافتراضي'),
                                            Infolists\Components\TextEntry::make('commission.sender_return_value')->label('قيمة مرتجع الراسل')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')->placeholder('الافتراضي'),
                                        ]),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCouriers::route('/'),
            'create' => Pages\CreateCourier::route('/create'),
            'edit' => Pages\EditCourier::route('/{record}/edit'),
            'view' => Pages\ViewCourier::route('/{record}'), // ✅ تأكد من وجود دي (لو مش موجودة، Filament بيستخدم المودال)
        ];
    }
}
