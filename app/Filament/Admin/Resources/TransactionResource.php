<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Branch;
use App\Models\Merchant;
use App\Models\Courier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'الأوراق المالية';
    protected static ?string $navigationGroup = 'الحسابات';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('تفاصيل المعاملة')
                ->schema([
                    // 1. اختيار نوع الحساب
                    Forms\Components\Select::make('transactable_type')
                        ->label('نوع الحساب')
                        ->options([
                            Branch::class => 'فرع',
                            Merchant::class => 'تاجر',
                            Courier::class => 'مندوب',
                        ])
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('transactable_id', null))
                        ->required(),

                    // 2. اختيار صاحب الحساب
                    Forms\Components\Select::make('transactable_id')
                        ->label('صاحب الحساب')
                        ->required()
                        ->searchable()
                        ->options(function (Forms\Get $get) {
                            $type = $get('transactable_type');
                            if (! $type) return [];

                            if ($type === Branch::class) return Branch::pluck('name', 'id');
                            if ($type === Merchant::class) return Merchant::pluck('name', 'id');
                            if ($type === Courier::class) return Courier::pluck('full_name', 'id');

                            return [];
                        })
                        ->live(),

                    // 3. اختيار نوع المحفظة (تم إزالة عهدة المناديب من الفرع)
                    Forms\Components\Select::make('wallet_type')
                        ->label('نوع المحفظة')
                        ->required()
                        ->options(function (Forms\Get $get) {
                            $type = $get('transactable_type');
                            // محافظ الفرع (تعديل)
                            if ($type === Branch::class) {
                                return [
                                    'total_balance' => 'الخزينة (الرصيد الإجمالي)',
                                    'commission_balance' => 'رصيد العمولة (أرباح)',
                                    // ❌ تم إزالة عهدة المناديب لأنها لا تعدل يدوياً
                                ];
                            }
                            // محافظ التاجر
                            if ($type === Merchant::class) {
                                return [
                                    'balance' => 'الرصيد الأساسي',
                                ];
                            }
                            // محافظ المندوب
                            if ($type === Courier::class) {
                                return [
                                    'custody_balance' => 'رصيد العهدة (شحنات)',
                                    'commission_balance' => 'رصيد العمولة',
                                ];
                            }
                            return [];
                        }),

                    // 4. نوع الحركة والمبلغ
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('type')
                            ->label('نوع الحركة')
                            ->options([
                                'credit' => 'إضافة للمحفظة (+)',
                                'debit' => 'خصم من المحفظة (-)',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('جنيه'),
                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('وصف المعاملة')
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transactable_name')
                    ->label('المستفيد')
                    ->getStateUsing(function ($record) {
                        if ($record->transactable_type === Courier::class) {
                            return $record->transactable?->full_name;
                        }
                        return $record->transactable?->name;
                    })
                    ->description(fn ($record) => match($record->transactable_type) {
                        Branch::class => 'فرع',
                        Merchant::class => 'تاجر',
                        Courier::class => 'مندوب',
                        default => ''
                    }),

                Tables\Columns\TextColumn::make('wallet_type')
                    ->label('المحفظة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'total_balance' => 'الخزينة',
                        'commission_balance' => 'العمولة',
                        'couriers_custody_balance' => 'عهدة مناديب',
                        'balance' => 'الرصيد',
                        'custody_balance' => 'العهدة',
                        default => $state
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                    ->weight('bold')
                    ->color(fn ($record) => $record->type === 'credit' ? 'success' : 'danger')
                    ->prefix(fn ($record) => $record->type === 'credit' ? '+ ' : '- '),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
        ];
    }
}
