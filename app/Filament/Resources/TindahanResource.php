<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TindahanResource\Pages;
use App\Filament\Resources\TindahanResource\RelationManagers;
use App\Models\Tindahan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TindahanResource extends Resource
{
    protected static ?string $model = Tindahan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prices & Markets';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\Select::make('market_id')
                            ->label('Market')
                            ->relationship('market', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('type')->maxLength(50),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Location')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('barangay')->maxLength(100),
                        Forms\Components\TextInput::make('municipality')->maxLength(100),
                        Forms\Components\TextInput::make('province')->maxLength(100),
                        Forms\Components\TextInput::make('region')->maxLength(50),
                    ]),
                Forms\Components\Section::make('Contact & Status')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('contact_number')->maxLength(20),
                        Forms\Components\TextInput::make('gcash_number')->maxLength(20),
                        Forms\Components\Toggle::make('is_active')->default(true),
                        Forms\Components\Toggle::make('is_verified')->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('market.name')
                    ->label('Market')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('municipality')->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTindahans::route('/'),
            'create' => Pages\CreateTindahan::route('/create'),
            'view' => Pages\ViewTindahan::route('/{record}'),
            'edit' => Pages\EditTindahan::route('/{record}/edit'),
        ];
    }
}
