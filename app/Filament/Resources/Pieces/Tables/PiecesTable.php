<?php

namespace App\Filament\Resources\Pieces\Tables;

use App\Enums\PieceStatus;
use App\Models\Piece;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class PiecesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('photos', 'collection'))
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->state(fn (Piece $record) => $record->mainPhoto()?->url(480))
                    ->square(),
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title_fr')
                    ->label('Titre')
                    ->searchable(['title_fr', 'title_en'])
                    ->description(fn (Piece $record) => $record->collection?->name_fr),
                TextColumn::make('price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => Money::format($state, 'fr'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('À la une')
                    ->icon(fn (bool $state) => $state ? Heroicon::Star : null)
                    ->color('warning')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Modifiée')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(PieceStatus::class),
                SelectFilter::make('collection')
                    ->label('Collection')
                    ->relationship('collection', 'name_fr'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view_on_site')
                    ->label('Voir sur le site')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Piece $record) => route('pieces.show', ['locale' => 'fr', 'piece' => $record]), shouldOpenInNewTab: true)
                    ->visible(fn (Piece $record) => in_array($record->status, PieceStatus::public(), true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Mettre en vente')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn (Collection $records) => $records
                            ->filter(fn (Piece $p) => in_array($p->status, [PieceStatus::Draft, PieceStatus::Archived], true))
                            ->each(fn (Piece $p) => $p->update(['status' => PieceStatus::Available]))),
                    BulkAction::make('archive')
                        ->label('Archiver (masquer du site)')
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records
                            ->reject(fn (Piece $p) => $p->status === PieceStatus::Reserved)
                            ->each(fn (Piece $p) => $p->update(['status' => PieceStatus::Archived]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
