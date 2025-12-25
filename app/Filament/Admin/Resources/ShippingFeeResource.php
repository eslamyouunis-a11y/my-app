<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShippingFeeResource\Pages;
use App\Filament\Admin\Resources\ShippingFeeResource\RelationManagers\ZoneFeesRelationManager;
use App\Models\ShippingFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingFeeResource extends Resource
{
    protected static ?string $model = ShippingFee::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'مصاريف الشحن';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('المسار')
                ->schema([
                    Forms\Components\Select::make('from_governorate_id')
                        ->label('من محافظة')
                        ->relationship('fromGovernorate', 'name')
                        ->required()
                        ->searchable()
                        ->preload(), // ✅ تحميل مسبق للقائمة

                    Forms\Components\Select::make('to_governorate_id')
                        ->label('إلى محافظة')
                        ->relationship('toGovernorate', 'name')
                        ->required()
                        ->searchable()
                        ->preload(), // ✅ تحميل مسبق للقائمة
                ])->columns(2),

            Forms\Components\Section::make('الأسعار والمدة')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        // باب البيت
                        Forms\Components\TextInput::make('home_price')
                            ->label('سعر للمنزل')
                            ->numeric()->required()->prefix(' جنيه'),
                        Forms\Components\TextInput::make('home_sla_days')
                            ->label('المدة (يوم)')->numeric()->required(),

                        // مكتب
                        Forms\Components\TextInput::make('office_price')
                            ->label('سعر للمكتب')
                            ->numeric()->required()->prefix(' جنيه'),
                        Forms\Components\TextInput::make('office_sla_days')
                            ->label('المدة (يوم)')->numeric()->required(),
                    ]),
                ]),

            Forms\Components\Toggle::make('is_active')
                ->label('مفعل')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromGovernorate.name')
                    ->label('من')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('toGovernorate.name')
                    ->label('إلى')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('home_price')
                    ->label('للمنزل')
                    ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('office_price')
                    ->label('للمكتب')
                    ->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('نشط'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_governorate_id')
                    ->relationship('fromGovernorate', 'name')
                    ->label('من')
                    ->searchable()
                    ->preload(), // ✅ تحميل مسبق في الفلتر أيضاً

                Tables\Filters\SelectFilter::make('to_governorate_id')
                    ->relationship('toGovernorate', 'name')
                    ->label('إلى')
                    ->searchable()
                    ->preload(), // ✅ تحميل مسبق في الفلتر أيضاً
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // تأكد إن الملف ده موجود لو هتستخدمه، أو شيله مؤقتاً لو لسه معملتوش
            ZoneFeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingFees::route('/'),
            'create' => Pages\CreateShippingFee::route('/create'),
            'edit' => Pages\EditShippingFee::route('/{record}/edit'),
        ];
    }
}
