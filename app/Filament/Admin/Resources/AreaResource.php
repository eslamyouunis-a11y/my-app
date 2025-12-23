<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AreaResource\Pages;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'المناطق';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('governorate_id')
                ->label('المحافظة')
                ->relationship('governorate', 'name')
                ->required()
                ->searchable()
                ->preload(), // ✅ تحميل مسبق

            Forms\Components\TextInput::make('name')
                ->label('اسم المنطقة')
                ->required(),

            Forms\Components\Toggle::make('is_active')
                ->label('مفعلة')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('governorate.name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('المنطقة')->searchable(),
                Tables\Columns\TextColumn::make('governorate.name')->label('المحافظة')->badge(),
                Tables\Columns\ToggleColumn::make('is_active')->label('الحالة'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('governorate_id')
                    ->relationship('governorate', 'name')
                    ->label('تصفية بالمحافظة')
                    ->preload(), // ✅ تحميل مسبق للفلتر
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
