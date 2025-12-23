<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GovernorateResource\Pages;
use App\Models\Governorate;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class GovernorateResource extends Resource
{
    protected static ?string $model = Governorate::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'المحافظات';
    protected static ?string $pluralLabel = 'المحافظات';
    protected static ?string $modelLabel = 'محافظة';
    protected static ?int $navigationSort = 1;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المحافظة')
                    ->required()
                    ->unique(ignoreRecord: true),

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
                    ->label('اسم المحافظة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('areas_count')
                    ->label('عدد المناطق')
                    ->counts('areas'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGovernorates::route('/'),
            'create' => Pages\CreateGovernorate::route('/create'),
            'edit' => Pages\EditGovernorate::route('/{record}/edit'),
        ];
    }
}
