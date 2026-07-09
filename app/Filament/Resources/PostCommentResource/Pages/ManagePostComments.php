<?php

namespace App\Filament\Resources\PostCommentResource\Pages;

use App\Filament\Resources\PostCommentResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePostComments extends ManageRecords
{
    protected static string $resource = PostCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
