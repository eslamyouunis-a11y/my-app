<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CourierResource\Pages;
use App\Models\Area;
use App\Models\Courier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'المناديب';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()
                ->tabs([

                    /* =========================
                     | بيانات المندوب
                     ========================= */
                    Forms\Components\Tabs\Tab::make('بيانات المندوب')
                        ->schema([

                            Forms\Components\TextInput::make('courier_code')
                                ->label('كود المندوب')
                                ->disabled()
                                ->dehydrated(true),

                            Forms\Components\TextInput::make('full_name')
                                ->label('اسم المندوب')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('branch_id')
                                ->label('الفرع')
                                ->relationship('branch', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('national_id')
                                ->label('الرقم القومي')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('phone')
                                ->label('رقم الهاتف')
                                ->tel()
                                ->required(),

                            Forms\Components\DatePicker::make('birth_date')
                                ->label('تاريخ الميلاد'),

                            Forms\Components\Select::make('vehicle_type')
                                ->label('نوع المركبة')
                                ->options([
                                    'motorcycle'       => 'موتوسيكل',
                                    'car'              => 'سيارة',
                                    'bicycle'          => 'عجلة',
                                    'public_transport' => 'مواصلات',
                                ])
                                ->required(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('مفعل')
                                ->default(true),

                        ])
                        ->columns(2),

                    /* =========================
                     | العنوان (Governorate/Area)
                     ========================= */
                    Forms\Components\Tabs\Tab::make('العنوان')
                        ->schema([

                            Forms\Components\Select::make('governorate_id')
                                ->label('المحافظة')
                                ->relationship('governorate', 'name')
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn ($set) => $set('area_id', null)),

                            Forms\Components\Select::make('area_id')
                                ->label('المنطقة')
                                ->options(fn (Get $get) =>
                                    $get('governorate_id')
                                        ? Area::where('governorate_id', $get('governorate_id'))
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray()
                                        : []
                                )
                                ->searchable()
                                ->required()
                                ->reactive()
                                // المدينة DB NOT NULL → نملأها تلقائي باسم المنطقة
                                ->afterStateUpdated(function ($set, $state) {
                                    $set('city', $state ? Area::whereKey($state)->value('name') : null);
                                }),

                            // city موجود في DB ومطلوب، لكن مش عايزينه يظهر → hidden
                            Forms\Components\Hidden::make('city')
                                ->dehydrated(true),

                            Forms\Components\Textarea::make('address')
                                ->label('العنوان التفصيلي')
                                ->required()
                                ->columnSpanFull(),

                        ])
                        ->columns(2),

                    /* =========================
                     | الرخص
                     ========================= */
                    Forms\Components\Tabs\Tab::make('الرخص')
                        ->schema([

                            Forms\Components\DatePicker::make('driving_license_expiry')
                                ->label('انتهاء رخصة القيادة'),

                            Forms\Components\DatePicker::make('vehicle_license_expiry')
                                ->label('انتهاء رخصة المركبة'),

                        ])
                        ->columns(2),

                    /* =========================
                     | الطوارئ
                     ========================= */
                    Forms\Components\Tabs\Tab::make('الطوارئ')
                        ->schema([

                            Forms\Components\TextInput::make('emergency_name')
                                ->label('اسم شخص الطوارئ'),

                            Forms\Components\TextInput::make('emergency_relation')
                                ->label('صلة القرابة'),

                            Forms\Components\TextInput::make('emergency_phone_1')
                                ->label('هاتف طوارئ 1'),

                            Forms\Components\TextInput::make('emergency_phone_2')
                                ->label('هاتف طوارئ 2'),

                            Forms\Components\Textarea::make('emergency_address')
                                ->label('عنوان الطوارئ')
                                ->columnSpanFull(),

                        ])
                        ->columns(2),

                    /* =========================
                     | العمولات (Hydration يدوي)
                     ========================= */
                    Forms\Components\Tabs\Tab::make('العمولات')
                        ->schema([

                            Forms\Components\Section::make('التسليم')
                                ->schema([
                                    Forms\Components\TextInput::make('commission.delivery_value')
                                        ->label('Delivery value')
                                        ->numeric()
                                        ->default(0)
                                        ->afterStateHydrated(fn ($component, $state, $record) =>
                                            $record?->commission
                                                ? $component->state($record->commission->delivery_value)
                                                : $component->state(0)
                                        ),

                                    Forms\Components\TextInput::make('commission.delivery_percentage')
                                        ->label('Delivery percentage')
                                        ->numeric()
                                        ->default(0)
                                        ->afterStateHydrated(fn ($component, $state, $record) =>
                                            $record?->commission
                                                ? $component->state($record->commission->delivery_percentage)
                                                : $component->state(0)
                                        ),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('مدفوع')
                                ->schema([
                                    Forms\Components\TextInput::make('commission.paid_value')
                                        ->label('Paid value')
                                        ->numeric()
                                        ->default(0)
                                        ->afterStateHydrated(fn ($component, $state, $record) =>
                                            $record?->commission
                                                ? $component->state($record->commission->paid_value)
                                                : $component->state(0)
                                        ),

                                    Forms\Components\TextInput::make('commission.paid_percentage')
                                        ->label('Paid percentage')
                                        ->numeric()
                                        ->default(0)
                                        ->afterStateHydrated(fn ($component, $state, $record) =>
                                            $record?->commission
                                                ? $component->state($record->commission->paid_percentage)
                                                : $component->state(0)
                                        ),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('على الراسل')
                                ->schema([
                                    Forms\Components\TextInput::make('commission.sender_return_value')
                                        ->label('Sender return value')
                                        ->numeric()
                                        ->default(0)
                                        ->afterStateHydrated(fn ($component, $state, $record) =>
                                            $record?->commission
                                                ? $component->state($record->commission->sender_return_value)
                                                : $component->state(0)
                                        ),

                                    Forms\Components\TextInput::make('commission.sender_return_percentage')
                                        ->label('Sender return percentage')
                                        ->numeric()
                                        ->default(0)
                                        ->afterStateHydrated(fn ($component, $state, $record) =>
                                            $record?->commission
                                                ? $component->state($record->commission->sender_return_percentage)
                                                : $component->state(0)
                                        ),
                                ])
                                ->columns(2),

                        ]),

                    /* =========================
                     | الدخول (User)
                     ========================= */
                    Forms\Components\Tabs\Tab::make('الدخول')
                        ->schema([

                            // الإيميل يظهر فقط (مش قابل للتعديل)
                            Forms\Components\TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->email()
                                ->disabled()
                                ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->rule(fn (?Courier $record) =>
                                    Rule::unique('users', 'email')->ignore($record?->user?->id)
                                )
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    // في edit: اعرض user.email
                                    if ($record?->user) {
                                        $component->state($record->user->email);
                                    }
                                })
                                ->dehydrated(false),

                            // الباسورد: ممكن تعدله فقط — مش إجباري في edit
                            Forms\Components\TextInput::make('password')
                                ->label('كلمة المرور')
                                ->password()
                                ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->rule(Password::default())
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('password_confirmation')
                                ->label('تأكيد كلمة المرور')
                                ->password()
                                ->same('password')
                                ->dehydrated(false),

                        ])
                        ->columns(2),

                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('courier_code')->label('الكود')->sortable(),
                Tables\Columns\TextColumn::make('full_name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('branch.name')->label('الفرع'),
                Tables\Columns\TextColumn::make('governorate.name')->label('المحافظة'),
                Tables\Columns\TextColumn::make('area.name')->label('المنطقة'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('مفعل'),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // حذف (Soft Delete) → يروح للمحذوفات
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),

                // استرجاع من المحذوفات
                Tables\Actions\RestoreAction::make()
                    ->label('استرجاع'),

                // حذف نهائي
                Tables\Actions\ForceDeleteAction::make()
                    ->label('حذف نهائي')
                    ->color('danger'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // مهم: signature الصحيح في Filament v3 + دعم المحذوفات
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
{
    return [
        'index' => Pages\ListCouriers::route('/'),
        'create' => Pages\CreateCourier::route('/create'),
        'edit' => Pages\EditCourier::route('/{record}/edit'),
        'view' => Pages\ViewCourier::route('/{record}'),
    ];
}

}
