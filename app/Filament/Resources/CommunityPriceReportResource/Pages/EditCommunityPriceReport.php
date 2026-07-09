<?php

namespace App\Filament\Resources\CommunityPriceReportResource\Pages;

use App\Filament\Resources\CommunityPriceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommunityPriceReport extends EditRecord
{
    protected static string $resource = CommunityPriceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
