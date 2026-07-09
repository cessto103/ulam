<?php

namespace App\Filament\Resources\ListingReportResource\Pages;

use App\Filament\Resources\ListingReportResource;
use Filament\Resources\Pages\ListRecords;

class ListListingReports extends ListRecords
{
    protected static string $resource = ListingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
