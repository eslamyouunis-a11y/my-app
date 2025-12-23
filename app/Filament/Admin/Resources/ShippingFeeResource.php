<?php

namespace App\Filament\Admin\Resources;

use App\Models\ShippingFee;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Filament\Admin\Resources\ShippingFeeResource\Pages;
use App\Filament\Admin\Resources\ShippingFeeResource\RelationManagers\ZoneFeesRelationManager;

class ShippingFeeResource extends Resource
{
    protected static ?string $model = ShippingFee::class;

    protected static ?string $navigationLabel = 'مصاريف الشحن';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'الإعدادات';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([

            Forms\Components\Section::make('الوجهة')
                ->schema([
                    Forms\Components\Select::make('from_governorate_id')
                        ->label('من محافظة')
                        ->relationship('fromGovernorate', 'name')
                        ->required(),

                    Forms\Components\Select::make('to_governorate_id')
                        ->label('إلى محافظة')
                        ->relationship('toGovernorate', 'name')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('باب البيت')
                ->schema([
                    Forms\Components\TextInput::make('home_price')
                        ->label('السعر')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('home_sla_days')
                        ->label('مدة التوصيل (أيام)')
                        ->numeric()
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('تسليم مكتب')
                ->schema([
                    Forms\Components\TextInput::make('office_price')
                        ->label('السعر')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('office_sla_days')
                        ->label('مدة التوصيل (أيام)')
                        ->numeric()
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Toggle::make('is_active')
                ->label('مفعل')
                ->default(true),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromGovernorate.name')->label('من'),
                Tables\Columns\TextColumn::make('toGovernorate.name')->label('إلى'),
                Tables\Columns\TextColumn::make('home_price')->money('EGP'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ZoneFeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingFees::route('/'),
            'create' => Pages\CreateShippingFee::route('/create'),
            'edit'   => Pages\EditShippingFee::route('/{record}/edit'),
        ];
    }
}
