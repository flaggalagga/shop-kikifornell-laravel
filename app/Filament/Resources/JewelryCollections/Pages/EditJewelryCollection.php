<?php

namespace App\Filament\Resources\JewelryCollections\Pages;

use App\Filament\Resources\JewelryCollections\JewelryCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJewelryCollection extends EditRecord
{
    protected static string $resource = JewelryCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
