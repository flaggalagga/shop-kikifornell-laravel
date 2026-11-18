<?php

namespace App\Filament\Resources\JewelryCollections;

use App\Filament\Resources\JewelryCollections\Pages\CreateJewelryCollection;
use App\Filament\Resources\JewelryCollections\Pages\EditJewelryCollection;
use App\Filament\Resources\JewelryCollections\Pages\ListJewelryCollections;
use App\Filament\Resources\JewelryCollections\Schemas\JewelryCollectionForm;
use App\Filament\Resources\JewelryCollections\Tables\JewelryCollectionsTable;
use App\Models\JewelryCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JewelryCollectionResource extends Resource
{
    protected static ?string $model = JewelryCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Boutique';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'collections';

    // The public site uses slugs in URLs; the admin uses ids.
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?string $modelLabel = 'collection';

    protected static ?string $pluralModelLabel = 'collections';

    protected static ?string $recordTitleAttribute = 'name_fr';

    public static function form(Schema $schema): Schema
    {
        return JewelryCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JewelryCollectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJewelryCollections::route('/'),
            'create' => CreateJewelryCollection::route('/create'),
            'edit' => EditJewelryCollection::route('/{record}/edit'),
        ];
    }
}
