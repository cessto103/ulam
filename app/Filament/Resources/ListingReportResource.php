<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingReportResource\Pages;
use App\Models\ListingReport;
use App\Models\Market;
use App\Models\Tindahan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ListingReportResource extends Resource
{
    protected static ?string $model = ListingReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Prices & Markets';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Reported Listings';

    private static function typeLabel(string $class): string
    {
        return match ($class) {
            Market::class => 'Market',
            Tindahan::class => 'Store/Stall',
            default => class_basename($class),
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('reporter')
                            ->label('Reported by')
                            ->content(fn ($record) => $record?->reporter?->name ?? '—'),
                        Forms\Components\Placeholder::make('reportable')
                            ->label('Listing')
                            ->content(fn ($record) => $record
                                ? self::typeLabel($record->reportable_type) . ': ' . ($record->reportable?->name ?? '(deleted)')
                                : '—'),
                        Forms\Components\Textarea::make('reason')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options(['pending' => 'Pending', 'actioned' => 'Actioned', 'dismissed' => 'Dismissed'])
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Reported by')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('reportable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => self::typeLabel($state))
                    ->colors([
                        'info' => Market::class,
                        'warning' => Tindahan::class,
                    ]),
                Tables\Columns\TextColumn::make('reportable.name')
                    ->label('Listing')
                    ->default('(deleted)'),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'pending',
                        'success' => 'actioned',
                        'gray' => 'dismissed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'actioned' => 'Actioned', 'dismissed' => 'Dismissed']),
                Tables\Filters\SelectFilter::make('reportable_type')
                    ->label('Type')
                    ->options([
                        Market::class => 'Market',
                        Tindahan::class => 'Store/Stall',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('banOwner')
                    ->label('Ban Owner')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (ListingReport $record) => $record->status === 'pending' && $record->reportable?->user)
                    ->requiresConfirmation()
                    ->modalDescription('This bans the user who owns the reported listing from the app entirely.')
                    ->action(function (ListingReport $record) {
                        $owner = $record->reportable?->user;
                        if (!$owner) {
                            Notification::make()->title('No owner found for this listing')->warning()->send();
                            return;
                        }
                        $owner->update(['banned_at' => now(), 'ban_reason' => 'Reported listing: ' . $record->reason]);
                        $record->update(['status' => 'actioned', 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
                        Notification::make()->title("Banned {$owner->name}")->success()->send();
                    }),
                Tables\Actions\Action::make('deactivateListing')
                    ->label('Deactivate Listing')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn (ListingReport $record) => $record->status === 'pending' && $record->reportable)
                    ->requiresConfirmation()
                    ->action(function (ListingReport $record) {
                        $record->reportable?->update(['is_active' => false]);
                        $record->update(['status' => 'actioned', 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
                        Notification::make()->title('Listing deactivated')->success()->send();
                    }),
                Tables\Actions\Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (ListingReport $record) => $record->status === 'pending')
                    ->action(fn (ListingReport $record) => $record->update([
                        'status' => 'dismissed', 'resolved_by' => auth()->id(), 'resolved_at' => now(),
                    ])),
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListingReports::route('/'),
            'view' => Pages\ViewListingReport::route('/{record}'),
        ];
    }
}
