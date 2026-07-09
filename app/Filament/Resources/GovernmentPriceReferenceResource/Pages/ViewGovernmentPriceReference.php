<?php

namespace App\Filament\Resources\GovernmentPriceReferenceResource\Pages;

use App\Filament\Resources\GovernmentPriceReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGovernmentPriceReference extends ViewRecord
{
    protected static string $resource = GovernmentPriceReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
