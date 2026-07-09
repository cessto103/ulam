<?php

namespace App\Filament\Resources\MarketPriceResource\Pages;

use App\Filament\Resources\MarketPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketPrice extends EditRecord
{
    protected static string $resource = MarketPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
