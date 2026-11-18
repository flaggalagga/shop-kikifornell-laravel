<?php

namespace App\Filament\Resources\JewelryCollections\Pages;

use App\Filament\Resources\JewelryCollections\JewelryCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJewelryCollections extends ListRecords
{
    protected static string $resource = JewelryCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
