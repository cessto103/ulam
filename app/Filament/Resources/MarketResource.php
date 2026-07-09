<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketResource\Pages;
use App\Filament\Resources\MarketResource\RelationManagers;
use App\Models\Market;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MarketResource extends Resource
{
    protected static ?string $model = Market::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prices & Markets';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Market Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options([
                                'wet_market' => 'Wet Market',
                                'palengke' => 'Palengke',
                                'supermarket' => 'Supermarket',
                                'grocery' => 'Grocery',
                                'tindahan' => 'Tindahan',
                            ])
                            ->default('wet_market')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('Location')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('barangay')->maxLength(100),
                        Forms\Components\TextInput::make('municipality')->maxLength(100),
                        Forms\Components\TextInput::make('province')->maxLength(100),
                        Forms\Components\TextInput::make('region')->maxLength(50),
                        Forms\Components\TextInput::make('latitude')->numeric(),
                        Forms\Components\TextInput::make('longitude')->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'wet_market',
                        'warning' => 'palengke',
                        'info' => 'supermarket',
                        'gray' => 'grocery',
                        'primary' => 'tindahan',
                    ]),
                Tables\Columns\TextColumn::make('barangay')->toggleable(),
                Tables\Columns\TextColumn::make('municipality')->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('tindahan_count')
                    ->label('Stalls')
                    ->counts('tindahan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('prices_count')
                    ->label('Prices')
                    ->counts('prices')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'wet_market' => 'Wet Market',
                        'palengke' => 'Palengke',
                        'supermarket' => 'Supermarket',
                        'grocery' => 'Grocery',
                        'tindahan' => 'Tindahan',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('municipality')
                    ->options(fn () => Market::query()
                        ->whereNotNull('municipality')
                        ->distinct()
                        ->orderBy('municipality')
                        ->pluck('municipality', 'municipality')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('refreshAi')
                    ->label('🤖 Refresh via AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This makes a real Claude API call (~$0.01-0.02) to search the web for current prices at this market.')
                    ->action(function ($record) {
                        $service = app(\App\Services\PriceIntelligenceService::class);
                        try {
                            $count = $service->refreshMarket($record);
                            \Filament\Notifications\Notification::make()
                                ->title("Refreshed {$count} prices")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Refresh failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            RelationManagers\PricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarkets::route('/'),
            'create' => Pages\CreateMarket::route('/create'),
            'view' => Pages\ViewMarket::route('/{record}'),
            'edit' => Pages\EditMarket::route('/{record}/edit'),
        ];
    }
}
