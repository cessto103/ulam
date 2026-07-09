<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Pages;
use App\Filament\Resources\RecipeResource\RelationManagers;
use App\Models\Recipe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Recipes';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basics')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                        Forms\Components\Textarea::make('description')->columnSpanFull(),
                        Forms\Components\TextInput::make('category')->maxLength(255),
                        Forms\Components\Select::make('source')
                            ->options([
                                'ai_generated' => 'AI Generated',
                                'community' => 'Community',
                                'admin' => 'Admin',
                                'official' => 'Official',
                            ])
                            ->required(),
                        Forms\Components\Select::make('budget_tag')
                            ->options([
                                'budget_100' => 'Budget 100',
                                'budget_200' => 'Budget 200',
                                'budget_400' => 'Budget 400',
                                'budget_400plus' => 'Budget 400+',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('estimated_cost')->numeric()->prefix('₱'),
                        Forms\Components\TextInput::make('servings')->numeric()->minValue(1),
                        Forms\Components\Select::make('difficulty')
                            ->options([
                                'madali' => 'Madali (Easy)',
                                'katamtaman' => 'Katamtaman (Medium)',
                                'mahirap' => 'Mahirap (Hard)',
                            ]),
                        Forms\Components\TextInput::make('prep_time_minutes')->numeric()->minValue(0)->suffix('min'),
                        Forms\Components\TextInput::make('cook_time_minutes')->numeric()->minValue(0)->suffix('min'),
                    ]),
                Forms\Components\Section::make('Content')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TagsInput::make('steps'),
                        Forms\Components\TagsInput::make('tips'),
                        Forms\Components\TagsInput::make('tags'),
                        Forms\Components\TagsInput::make('dietary_flags'),
                    ]),
                Forms\Components\Section::make('Media & Display')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('image_url')->maxLength(255)->columnSpanFull(),
                        Forms\Components\TagsInput::make('image_urls')->columnSpanFull(),
                        Forms\Components\TextInput::make('youtube_url')->maxLength(255)->columnSpanFull(),
                        Forms\Components\TextInput::make('collage_style')->maxLength(20),
                        Forms\Components\TextInput::make('gradient_key')->maxLength(10),
                        Forms\Components\TextInput::make('font_key')->maxLength(20),
                    ]),
                Forms\Components\Section::make('Publishing')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_published'),
                        Forms\Components\Toggle::make('is_premium_only'),
                        Forms\Components\Select::make('user_id')
                            ->label('Owner')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->searchable()->toggleable(),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['official', 'admin'], true),
                        'gray' => 'community',
                        'info' => 'ai_generated',
                    ]),
                Tables\Columns\TextColumn::make('budget_tag'),
                Tables\Columns\TextColumn::make('estimated_cost')->money('PHP')->sortable(),
                Tables\Columns\TextColumn::make('servings')->sortable(),
                Tables\Columns\TextColumn::make('difficulty'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->sortable(),
                Tables\Columns\IconColumn::make('is_premium_only')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('average_rating')->numeric(2)->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('save_count')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'ai_generated' => 'AI Generated',
                        'community' => 'Community',
                        'admin' => 'Admin',
                        'official' => 'Official',
                    ]),
                Tables\Filters\SelectFilter::make('budget_tag')
                    ->options([
                        'budget_100' => 'Budget 100',
                        'budget_200' => 'Budget 200',
                        'budget_400' => 'Budget 400',
                        'budget_400plus' => 'Budget 400+',
                    ]),
                Tables\Filters\TernaryFilter::make('is_published'),
            ])
            ->actions([
                Tables\Actions\Action::make('togglePublish')
                    ->label(fn ($record) => $record->is_published ? 'Unpublish' : 'Publish')
                    ->icon(fn ($record) => $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_published ? 'gray' : 'success')
                    ->action(fn ($record) => $record->update(['is_published' => ! $record->is_published])),
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
            RelationManagers\IngredientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'view' => Pages\ViewRecipe::route('/{record}'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }
}
