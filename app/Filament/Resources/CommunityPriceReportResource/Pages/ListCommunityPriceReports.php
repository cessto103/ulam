<?php

namespace App\Filament\Resources\CommunityPriceReportResource\Pages;

use App\Filament\Resources\CommunityPriceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommunityPriceReports extends ListRecords
{
    protected static string $resource = CommunityPriceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
