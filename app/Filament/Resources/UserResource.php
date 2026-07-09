<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('username')->required()->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                        Forms\Components\TextInput::make('bio')->maxLength(160),
                        Forms\Components\TextInput::make('household_size')->numeric()->minValue(1)->maxValue(20),
                        Forms\Components\Toggle::make('onboarding_completed'),
                    ]),
                Forms\Components\Section::make('Location')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('barangay')->maxLength(100),
                        Forms\Components\TextInput::make('municipality')->maxLength(100),
                        Forms\Components\TextInput::make('province')->maxLength(100),
                        Forms\Components\TextInput::make('region')->maxLength(50),
                    ]),
                Forms\Components\Section::make('Account & Access')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options(['user' => 'User', 'admin' => 'Admin'])
                            ->required(),
                        Forms\Components\Select::make('plan')
                            ->options(['libre' => 'Libre (Free)', 'premium' => 'Premium'])
                            ->required(),
                        Forms\Components\DateTimePicker::make('premium_expires_at'),
                        Forms\Components\DateTimePicker::make('banned_at')
                            ->label('Banned at'),
                        Forms\Components\TextInput::make('ban_reason')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Gamification (read-mostly)')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('xp')->numeric(),
                        Forms\Components\TextInput::make('level')->numeric(),
                        Forms\Components\TextInput::make('streak_days')->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('municipality')->toggleable(),
                Tables\Columns\BadgeColumn::make('plan')
                    ->colors(['success' => 'premium', 'gray' => 'libre']),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors(['warning' => 'admin', 'gray' => 'user']),
                Tables\Columns\TextColumn::make('xp')->sortable(),
                Tables\Columns\TextColumn::make('level')->sortable(),
                Tables\Columns\TextColumn::make('streak_days')->sortable()->toggleable(),
                Tables\Columns\IconColumn::make('banned_at')
                    ->label('Banned')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->banned_at)
                    ->trueColor('danger')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options(['libre' => 'Libre (Free)', 'premium' => 'Premium']),
                Tables\Filters\SelectFilter::make('role')
                    ->options(['user' => 'User', 'admin' => 'Admin']),
                Tables\Filters\TernaryFilter::make('banned')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('banned_at'),
                        false: fn (Builder $query) => $query->whereNull('banned_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => !$record->banned_at)
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('ban_reason')->label('Reason')->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'banned_at' => now(),
                        'ban_reason' => $data['ban_reason'],
                    ])),
                Tables\Actions\Action::make('unban')
                    ->label('Unban')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => (bool) $record->banned_at)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['banned_at' => null, 'ban_reason' => null])),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
