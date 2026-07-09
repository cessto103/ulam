<?php

namespace App\Filament\Resources\GovernmentPriceReferenceResource\Pages;

use App\Filament\Resources\GovernmentPriceReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGovernmentPriceReferences extends ListRecords
{
    protected static string $resource = GovernmentPriceReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
