<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GovernmentPriceReferenceResource\Pages;
use App\Filament\Resources\GovernmentPriceReferenceResource\RelationManagers;
use App\Models\GovernmentPriceReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GovernmentPriceReferenceResource extends Resource
{
    protected static ?string $model = GovernmentPriceReference::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prices & Markets';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'item_name';

    private const SOURCES = [
        'da_bantay_presyo' => 'DA Bantay Presyo',
        'dti_srp' => 'DTI SRP',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reference Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('source')
                            ->options(self::SOURCES)
                            ->required(),
                        Forms\Components\TextInput::make('item_name')->required()->maxLength(100),
                        Forms\Components\TextInput::make('category')->maxLength(50),
                        Forms\Components\TextInput::make('unit')->required()->maxLength(30),
                        Forms\Components\TextInput::make('price_min')->numeric()->required(),
                        Forms\Components\TextInput::make('price_max')->numeric()->required(),
                        Forms\Components\TextInput::make('region')->maxLength(50),
                        Forms\Components\DatePicker::make('bulletin_date'),
                        Forms\Components\TextInput::make('source_note')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'success' => 'da_bantay_presyo',
                        'info' => 'dti_srp',
                    ]),
                Tables\Columns\TextColumn::make('item_name')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('price_min')->money('PHP'),
                Tables\Columns\TextColumn::make('price_max')->money('PHP'),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('region'),
                Tables\Columns\TextColumn::make('bulletin_date')->date(),
                Tables\Columns\TextColumn::make('source_note')->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(self::SOURCES),
                Tables\Filters\SelectFilter::make('region')
                    ->options(fn () => GovernmentPriceReference::query()
                        ->whereNotNull('region')
                        ->distinct()
                        ->orderBy('region')
                        ->pluck('region', 'region')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGovernmentPriceReferences::route('/'),
            'create' => Pages\CreateGovernmentPriceReference::route('/create'),
            'view' => Pages\ViewGovernmentPriceReference::route('/{record}'),
            'edit' => Pages\EditGovernmentPriceReference::route('/{record}/edit'),
        ];
    }
}
