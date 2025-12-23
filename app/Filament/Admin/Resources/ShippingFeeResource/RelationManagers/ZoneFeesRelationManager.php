<?php

namespace App\Filament\Admin\Resources\ShippingFeeResource\RelationManagers;

use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ZoneFeesRelationManager extends RelationManager
{
    protected static string $relationship = 'zoneFees';

    protected static ?string $title = 'تخصيص المناطق';

    public function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('area_id')
                ->label('المنطقة')
                ->options(function () {
                    /** @var \App\Models\ShippingFee|null $shippingFee */
                    $shippingFee = $this->getOwnerRecord();

                    if (! $shippingFee) {
                        return [];
                    }

                    return Area::query()
                        ->where('governorate_id', $shippingFee->to_governorate_id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->required(),

            Forms\Components\Section::make('باب البيت')
                ->schema([
                    Forms\Components\TextInput::make('home_price')
                        ->label('السعر')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('home_sla_days')
                        ->label('مدة التوصيل (أيام)')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Toggle::make('is_active')
                ->label('مفعل')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('area.name')
                    ->label('المنطقة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('home_price')
                    ->label('سعر باب البيت')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('home_sla_days')
                    ->label('أيام التوصيل'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة منطقة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
