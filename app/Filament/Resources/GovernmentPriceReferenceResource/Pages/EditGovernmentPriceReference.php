<?php

namespace App\Filament\Resources\GovernmentPriceReferenceResource\Pages;

use App\Filament\Resources\GovernmentPriceReferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGovernmentPriceReference extends EditRecord
{
    protected static string $resource = GovernmentPriceReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
