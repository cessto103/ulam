<?php

namespace App\Filament\Resources\TindahanResource\Pages;

use App\Filament\Resources\TindahanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTindahan extends EditRecord
{
    protected static string $resource = TindahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
