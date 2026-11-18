<?php

namespace App\Filament\Resources\JewelryCollections\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JewelryCollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name_fr')->label('Nom')->searchable(['name_fr', 'name_en']),
                TextColumn::make('pieces_count')->label('Pièces')->counts('pieces'),
                IconColumn::make('is_published')->label('Visible')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
