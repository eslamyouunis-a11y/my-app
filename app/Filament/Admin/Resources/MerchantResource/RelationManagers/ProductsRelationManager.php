<?php

namespace App\Filament\Admin\Resources\MerchantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $title = 'منتجات التاجر';
    protected static ?string $icon = 'heroicon-m-shopping-bag';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المنتج')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('sku')
                    ->label('كود المنتج')
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('default_weight')
                    ->label('الوزن الافتراضي (كجم)')
                    ->numeric()
                    ->default(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('المنتج')->searchable(),
                Tables\Columns\TextColumn::make('sku')->label('كود المنتج')->searchable(),
                Tables\Columns\TextColumn::make('default_weight')->label('الوزن')->suffix(' كجم'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة منتج'),
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
