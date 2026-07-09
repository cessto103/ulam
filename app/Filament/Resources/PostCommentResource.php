<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostCommentResource\Pages;
use App\Models\PostComment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostCommentResource extends Resource
{
    protected static ?string $model = PostComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Community';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'body';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Comment')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('user.name')
                            ->label('Author')
                            ->content(fn ($record) => $record?->user?->name ?? '—'),
                        Forms\Components\Placeholder::make('post.body')
                            ->label('On Post')
                            ->content(fn ($record) => $record?->post
                                ? str($record->post->body)->limit(80)
                                : '—'),
                        Forms\Components\Textarea::make('body')
                            ->label('Comment')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('parent_id')
                            ->label('Reply Status')
                            ->content(fn ($record) => $record?->parent_id
                                ? "Reply to comment #{$record->parent_id}"
                                : 'Top-level comment'),
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Posted At')
                            ->content(fn ($record) => $record?->created_at?->format('M j, Y g:i A') ?? '—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('post.body')
                    ->label('On post')
                    ->limit(40),
                Tables\Columns\TextColumn::make('body')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('parent_id')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => $record->parent_id ? 'Reply' : 'Top-level')
                    ->colors([
                        'warning' => 'Reply',
                        'gray' => 'Top-level',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_reply')
                    ->label('Is a reply')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('parent_id'),
                        false: fn (Builder $query) => $query->whereNull('parent_id'),
                    ),
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePostComments::route('/'),
        ];
    }
}
