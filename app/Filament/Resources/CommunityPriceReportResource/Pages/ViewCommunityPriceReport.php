<?php

namespace App\Filament\Resources\CommunityPriceReportResource\Pages;

use App\Filament\Resources\CommunityPriceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityPriceReport extends ViewRecord
{
    protected static string $resource = CommunityPriceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
