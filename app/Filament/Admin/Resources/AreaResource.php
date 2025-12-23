<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AreaResource\Pages;
use App\Models\Area;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'المناطق';
    protected static ?string $pluralLabel = 'المناطق';
    protected static ?string $modelLabel = 'منطقة';
    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('governorate_id')
                    ->label('المحافظة')
                    ->relationship('governorate', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('اسم المنطقة')
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('مفعلة')
                    ->default(true),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المنطقة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('governorate.name')
                    ->label('المحافظة'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->defaultSort('name');
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
