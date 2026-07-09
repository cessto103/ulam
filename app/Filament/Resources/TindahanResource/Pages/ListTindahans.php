<?php

namespace App\Filament\Resources\TindahanResource\Pages;

use App\Filament\Resources\TindahanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTindahans extends ListRecords
{
    protected static string $resource = TindahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
