<?php

namespace App\Filament\Resources\Pieces;

use App\Filament\Resources\Pieces\Pages\CreatePiece;
use App\Filament\Resources\Pieces\Pages\EditPiece;
use App\Filament\Resources\Pieces\Pages\ListPieces;
use App\Filament\Resources\Pieces\Schemas\PieceForm;
use App\Filament\Resources\Pieces\Tables\PiecesTable;
use App\Models\Piece;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PieceResource extends Resource
{
    protected static ?string $model = Piece::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Boutique';

    protected static ?int $navigationSort = 1;

    // The public site uses slugs in URLs; the admin uses ids.
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?string $modelLabel = 'pièce';

    protected static ?string $pluralModelLabel = 'pièces';

    protected static ?string $recordTitleAttribute = 'title_fr';

    public static function form(Schema $schema): Schema
    {
        return PieceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PiecesTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'title_fr', 'title_en'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPieces::route('/'),
            'create' => CreatePiece::route('/create'),
            'edit' => EditPiece::route('/{record}/edit'),
        ];
    }
}
