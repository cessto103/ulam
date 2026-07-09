<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketPriceResource\Pages;
use App\Filament\Resources\MarketPriceResource\RelationManagers;
use App\Models\MarketPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MarketPriceResource extends Resource
{
    protected static ?string $model = MarketPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prices & Markets';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'item_name';

    private const CATEGORIES = ['isda', 'karne', 'gulay', 'bigas', 'prutas', 'sangkap', 'itlog', 'manok', 'baboy', 'baka', 'iba pa'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Price Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('item_name')->required()->maxLength(100),
                        Forms\Components\Select::make('category')
                            ->options(array_combine(self::CATEGORIES, self::CATEGORIES)),
                        Forms\Components\TextInput::make('price_per_unit')->numeric()->required(),
                        Forms\Components\TextInput::make('unit')->required()->maxLength(30),
                        Forms\Components\Toggle::make('is_available')->default(true),
                    ]),
                Forms\Components\Section::make('Source')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('market_id')
                            ->label('Market')
                            ->relationship('market', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tindahan_id')
                            ->label('Tindahan')
                            ->relationship('tindahan', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_name')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('market.name')
                    ->label('Market')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tindahan.name')
                    ->label('Tindahan')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'isda' => 'Isda',
                        'karne' => 'Karne',
                        'gulay' => 'Gulay',
                        'bigas' => 'Bigas',
                        'prutas' => 'Prutas',
                        'sangkap' => 'Sangkap',
                        'itlog' => 'Itlog',
                        'manok' => 'Manok',
                        'baboy' => 'Baboy',
                        'baka' => 'Baka',
                        'iba pa' => 'Iba pa',
                    ]),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Available'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketPrices::route('/'),
            'create' => Pages\CreateMarketPrice::route('/create'),
            'view' => Pages\ViewMarketPrice::route('/{record}'),
            'edit' => Pages\EditMarketPrice::route('/{record}/edit'),
        ];
    }
}
