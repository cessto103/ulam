<?php

namespace App\Filament\Resources\TindahanResource\Pages;

use App\Filament\Resources\TindahanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTindahan extends ViewRecord
{
    protected static string $resource = TindahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
