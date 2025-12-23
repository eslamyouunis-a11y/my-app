<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'الفروع';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Tabs::make()
                ->tabs([

                    Forms\Components\Tabs\Tab::make('معلومات الفرع')
                        ->schema([

                            Forms\Components\TextInput::make('name')
                                ->label('اسم الفرع')
                                ->required(),

                            Forms\Components\Select::make('branch_type')
                                ->label('نوع الفرع')
                                ->options(Branch::branchTypes())
                                ->required(),

                            Forms\Components\TextInput::make('manager_name')
                                ->label('اسم مدير الفرع')
                                ->required(),

                            Forms\Components\TextInput::make('manager_phone')
                                ->label('موبايل المدير')
                                ->tel()
                                ->required(),

                            Forms\Components\TextInput::make('email')
                                ->label('إيميل دخول الفرع')
                                ->email()
                                ->required()
                                // ✅ Unique على جدول branches (طبيعي)
                                ->rule(function (?Branch $record) {
                                    return Rule::unique('branches', 'email')->ignore($record?->id);
                                })
                                // ✅ Unique على جدول users لكن ignore = user_id المرتبط بالفرع
                                ->rule(function (?Branch $record) {
                                    $ignoreUserId = $record?->user?->id;
                                    return Rule::unique('users', 'email')->ignore($ignoreUserId);
                                }),

                            Forms\Components\TextInput::make('password')
                                ->label('كلمة المرور')
                                ->password()
                                ->required(fn ($livewire) =>
                                    $livewire instanceof \Filament\Resources\Pages\CreateRecord
                                )
                                ->rule(Password::default())
                                ->dehydrated(fn ($state) => filled($state)),

                            Forms\Components\TextInput::make('password_confirmation')
                                ->label('تأكيد كلمة المرور')
                                ->password()
                                ->same('password')
                                ->dehydrated(false),

                            Forms\Components\Select::make('governorate_id')
                                ->label('المحافظة')
                                ->relationship('governorate', 'name')
                                ->required(),

                            Forms\Components\Textarea::make('address')
                                ->label('عنوان الفرع')
                                ->required(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('مفعل')
                                ->default(true),
                        ])
                        ->columns(2),

                    Forms\Components\Tabs\Tab::make('العمولات')
                        ->schema([
                            Forms\Components\Section::make('تسليم عادي')
                                ->schema([
                                    Forms\Components\TextInput::make('normal_delivery_fee')->numeric(),
                                    Forms\Components\TextInput::make('normal_delivery_percent')->numeric(),
                                    Forms\Components\TextInput::make('normal_cod_fee')->numeric(),
                                    Forms\Components\TextInput::make('normal_cod_percent')->numeric(),
                                    Forms\Components\TextInput::make('normal_paid_return_fee')->numeric(),
                                    Forms\Components\TextInput::make('normal_paid_return_percent')->numeric(),
                                ])->columns(2),

                            Forms\Components\Section::make('تسليم مكتب')
                                ->schema([
                                    Forms\Components\TextInput::make('office_delivery_fee')->numeric(),
                                    Forms\Components\TextInput::make('office_delivery_percent')->numeric(),
                                    Forms\Components\TextInput::make('office_cod_fee')->numeric(),
                                    Forms\Components\TextInput::make('office_cod_percent')->numeric(),
                                    Forms\Components\TextInput::make('office_paid_return_fee')->numeric(),
                                    Forms\Components\TextInput::make('office_paid_return_percent')->numeric(),
                                ])->columns(2),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('branch_number')->label('رقم'),
            Tables\Columns\TextColumn::make('name')->label('الفرع'),
            Tables\Columns\TextColumn::make('branch_type')->label('النوع'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
