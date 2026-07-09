<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommunityPriceReportResource\Pages;
use App\Filament\Resources\CommunityPriceReportResource\RelationManagers;
use App\Models\CommunityPriceReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommunityPriceReportResource extends Resource
{
    protected static ?string $model = CommunityPriceReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prices & Markets';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'item_name';

    private const CATEGORIES = ['isda', 'karne', 'gulay', 'bigas', 'prutas', 'sangkap', 'itlog', 'manok', 'baboy', 'baka', 'iba pa'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Reporter')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('tindahan_id')
                            ->label('Tindahan')
                            ->relationship('tindahan', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('item_name')->required()->maxLength(100),
                        Forms\Components\Select::make('category')
                            ->options(array_combine(self::CATEGORIES, self::CATEGORIES)),
                        Forms\Components\TextInput::make('reported_price')->numeric()->required(),
                        Forms\Components\TextInput::make('unit')->required()->maxLength(30),
                        Forms\Components\Toggle::make('is_verified')->default(false),
                    ]),
                Forms\Components\Section::make('Location')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('barangay')->maxLength(100),
                        Forms\Components\TextInput::make('municipality')->maxLength(100),
                        Forms\Components\TextInput::make('province')->maxLength(100),
                    ]),
                Forms\Components\Section::make('Votes (read-mostly)')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('upvotes')->numeric(),
                        Forms\Components\TextInput::make('downvotes')->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_name')->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Reporter')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reported_price')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('municipality')->toggleable(),
                Tables\Columns\TextColumn::make('upvotes')->sortable(),
                Tables\Columns\TextColumn::make('downvotes')->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
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
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Mark Verified')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_verified)
                    ->action(fn ($record) => $record->update(['is_verified' => true])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCommunityPriceReports::route('/'),
            'create' => Pages\CreateCommunityPriceReport::route('/create'),
            'view' => Pages\ViewCommunityPriceReport::route('/{record}'),
            'edit' => Pages\EditCommunityPriceReport::route('/{record}/edit'),
        ];
    }
}
