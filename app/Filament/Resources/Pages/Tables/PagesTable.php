<?php

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title_fr')->label('Titre')->searchable(['title_fr', 'title_en']),
                TextColumn::make('slug')->label('Adresse')->prefix('/info/'),
                IconColumn::make('show_in_footer')->label('Pied de page')->boolean(),
                TextColumn::make('updated_at')->label('Modifiée')->since(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
