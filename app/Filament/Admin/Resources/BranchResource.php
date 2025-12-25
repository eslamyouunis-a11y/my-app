<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BranchResource\Pages;
use App\Models\Branch;
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
// ✅ استدعاءات Infolist
use Filament\Infolists;
use Filament\Infolists\Infolist;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'الفروع';
    protected static ?string $modelLabel = 'فرع';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('BranchDetails')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('البيانات الأساسية')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('name')->label('اسم الفرع')->required(),
                                    Forms\Components\Select::make('branch_type')->label('نوع الفرع')->options(Branch::branchTypes())->required()->searchable(),
                                    Forms\Components\Select::make('governorate_id')->label('المحافظة')->relationship('governorate', 'name')->searchable()->preload()->required(),
                                    TextInput::make('address')->label('العنوان')->columnSpanFull(),
                                    Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
                                ])->columns(2),
                        ]),
                    Forms\Components\Tabs\Tab::make('حساب المدير')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('manager_name')->label('اسم المدير')->required(),
                                    TextInput::make('manager_phone')->label('رقم الموبايل')->tel()->required(),
                                    TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique('users', 'email', ignoreRecord: true),
                                    TextInput::make('password')->label('كلمة المرور')->password()->revealable()->required(fn ($livewire) => $livewire instanceof Pages\CreateBranch)->helperText('اتركه فارغاً في حالة التعديل لإبقاء كلمة المرور القديمة.'),
                                ])->columns(2),
                        ]),
                    Forms\Components\Tabs\Tab::make('العمولات')
                        ->schema([
                            Section::make('توصيل للمنزل (Doorstep)')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Forms\Components\Group::make([
                                            TextInput::make('normal_delivery_fee')->label('تسليم (قيمة)')->numeric()->default(0),
                                            TextInput::make('normal_delivery_percent')->label('تسليم (%)')->numeric()->default(0),
                                        ])->label('تسليم ناجح'),
                                        Forms\Components\Group::make([
                                            TextInput::make('normal_paid_return_fee')->label('مرتجع مدفوع (قيمة)')->numeric()->default(0),
                                            TextInput::make('normal_paid_return_percent')->label('مرتجع مدفوع (%)')->numeric()->default(0),
                                        ])->label('مرتجع مدفوع'),
                                        Forms\Components\Group::make([
                                            TextInput::make('normal_return_on_sender_fee')->label('مرتجع راسل (قيمة)')->numeric()->default(0),
                                            TextInput::make('normal_return_on_sender_percent')->label('مرتجع راسل (%)')->numeric()->default(0),
                                        ])->label('مرتجع على الراسل'),
                                    ]),
                                ]),
                            Section::make('توصيل للمكتب (Office)')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Forms\Components\Group::make([
                                            TextInput::make('office_delivery_fee')->label('تسليم (قيمة)')->numeric()->default(0),
                                            TextInput::make('office_delivery_percent')->label('تسليم (%)')->numeric()->default(0),
                                        ])->label('تسليم ناجح فقط'),
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
                Tables\Columns\TextColumn::make('branch_number')->label('#')->sortable()->weight('bold')->color('primary'),
                Tables\Columns\TextColumn::make('name')->label('الفرع')->searchable(),
                Tables\Columns\TextColumn::make('governorate.name')->label('المحافظة')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('couriers_count')->label('المناديب')->counts('couriers')->badge()->color('success'),
                Tables\Columns\TextColumn::make('branch_type')->label('النوع')->badge()->colors(['primary' => 'direct', 'warning' => 'franchise', 'info' => 'hub'])->formatStateUsing(fn ($state) => Branch::branchTypes()[$state] ?? $state),
                Tables\Columns\ToggleColumn::make('is_active')->label('الحالة'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('governorate_id')->relationship('governorate', 'name')->preload(),
                Tables\Filters\SelectFilter::make('branch_type')->options(Branch::branchTypes()),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // ✅
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

    // ✅ تصميم صفحة العرض
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. أرصدة الفرع
                Infolists\Components\Section::make('الموقف المالي للفرع')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('total_balance')
                                ->label('الخزينة (إجمالي)')
                                ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->color('info')
                                ->icon('heroicon-m-building-library'),

                            Infolists\Components\TextEntry::make('commission_balance')
                                ->label('رصيد الأرباح (العمولة)')
                                ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->color('success')
                                ->icon('heroicon-m-currency-dollar'),

                            Infolists\Components\TextEntry::make('couriers_custody_balance')
                                ->label('عهدة مع المناديب')
                                ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->color('warning')
                                ->icon('heroicon-m-users'),
                        ]),
                    ]),

                // 2. التفاصيل
                Infolists\Components\Tabs::make('Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('البيانات الأساسية')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Infolists\Components\Grid::make(2)->schema([
                                    Infolists\Components\TextEntry::make('name')->label('اسم الفرع'),
                                    Infolists\Components\TextEntry::make('branch_type')
                                        ->label('نوع الفرع')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => \App\Models\Branch::branchTypes()[$state] ?? $state),
                                    Infolists\Components\TextEntry::make('manager_name')->label('المدير'),
                                    Infolists\Components\TextEntry::make('manager_phone')->label('هاتف المدير'),
                                    Infolists\Components\TextEntry::make('address')->label('العنوان'),
                                    Infolists\Components\TextEntry::make('couriers_count')
                                        ->label('عدد المناديب')
                                        ->state(fn ($record) => $record->couriers()->count())
                                        ->badge(),
                                ]),
                            ]),

                         Infolists\Components\Tabs\Tab::make('هيكلة العمولات')
                            ->icon('heroicon-m-table-cells')
                            ->schema([
                                Infolists\Components\Grid::make(2)->schema([
                                    Infolists\Components\Section::make('توصيل للمنزل')
                                        ->schema([
                                            Infolists\Components\TextEntry::make('normal_delivery_fee')->label('قيمة')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                            Infolists\Components\TextEntry::make('normal_delivery_percent')->label('نسبة')->suffix('%'),
                                        ])->columns(2),
                                    Infolists\Components\Section::make('توصيل مكتب')
                                        ->schema([
                                            Infolists\Components\TextEntry::make('office_delivery_fee')->label('قيمة')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                                            Infolists\Components\TextEntry::make('office_delivery_percent')->label('نسبة')->suffix('%'),
                                        ])->columns(2),
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
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
            'view' => Pages\ViewBranch::route('/{record}'), // ✅
        ];
    }
}
