<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Community';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'body';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Post Content')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('body')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('post_type')
                            ->options([
                                'recipe_share' => 'Recipe Share',
                                'price_tip' => 'Price Tip',
                                'budget_win' => 'Budget Win',
                                'general' => 'General',
                            ])
                            ->disabled(),
                        Forms\Components\Toggle::make('is_sponsored'),
                    ]),
                Forms\Components\Section::make('Location (read-only)')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('barangay')->disabled(),
                        Forms\Components\TextInput::make('municipality')->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('post_type')
                    ->colors([
                        'success' => 'recipe_share',
                        'warning' => 'price_tip',
                        'info' => 'budget_win',
                        'gray' => 'general',
                    ]),
                Tables\Columns\TextColumn::make('body')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('municipality')->toggleable(),
                Tables\Columns\TextColumn::make('puso_count')->sortable(),
                Tables\Columns\TextColumn::make('dislike_count')->sortable(),
                Tables\Columns\TextColumn::make('comments_count')->sortable(),
                Tables\Columns\IconColumn::make('is_sponsored')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('post_type')
                    ->options([
                        'recipe_share' => 'Recipe Share',
                        'price_tip' => 'Price Tip',
                        'budget_win' => 'Budget Win',
                        'general' => 'General',
                    ]),
                Tables\Filters\TernaryFilter::make('is_sponsored'),
                Tables\Filters\SelectFilter::make('municipality')
                    ->options(fn () => Post::query()
                        ->whereNotNull('municipality')
                        ->distinct()
                        ->orderBy('municipality')
                        ->pluck('municipality', 'municipality')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
