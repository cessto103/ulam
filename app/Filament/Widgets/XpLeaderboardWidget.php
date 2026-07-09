<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class XpLeaderboardWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 10 by XP';

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()->orderByDesc('xp')->limit(10)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('municipality')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('level')
                    ->badge(),
                Tables\Columns\TextColumn::make('xp')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('streak_days')
                    ->label('Streak')
                    ->numeric(),
            ]);
    }
}
