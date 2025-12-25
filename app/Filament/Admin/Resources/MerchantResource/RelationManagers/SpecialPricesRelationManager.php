<?php

namespace App\Filament\Admin\Resources\MerchantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SpecialPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'specialPrices';
    protected static ?string $title = 'قائمة الأسعار الخاصة';
    protected static ?string $icon = 'heroicon-m-currency-dollar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('governorate_id')
                    ->label('المحافظة')
                    ->relationship('governorate', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('area_id', null)),

                Forms\Components\Select::make('area_id')
                    ->label('المنطقة (اختياري)')
                    ->relationship('area', 'name', modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                        return $query->where('governorate_id', $get('governorate_id'));
                    })
                    ->searchable()
                    ->preload()
                    ->helperText('اتركه فارغاً لتطبيق السعر على كامل المحافظة')
                    ->disabled(fn (Forms\Get $get) => ! $get('governorate_id')),

                Forms\Components\TextInput::make('price')
                    ->label('سعر الشحن الخاص')
                    ->numeric()
                    ->required()
                    ->suffix('جنيه'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('price')
            ->columns([
                Tables\Columns\TextColumn::make('governorate.name')->label('المحافظة')->sortable(),
                Tables\Columns\TextColumn::make('area.name')->label('المنطقة')->placeholder('كل المناطق'),
                Tables\Columns\TextColumn::make('price')->label('السعر')->numeric(decimalPlaces: 0, locale: 'ar-u-nu-latn')->suffix(' جنيه')->sortable()->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة سعر خاص'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
