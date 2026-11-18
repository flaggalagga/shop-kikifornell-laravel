<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextEntry::make('name')->label('Nom'),
                TextEntry::make('email')->label('E-mail')->copyable(),
                TextEntry::make('piece.reference')
                    ->label('Pièce')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, Inquiry $record) => $state.' — '.$record->piece?->title_fr),
                TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
                TextEntry::make('locale')->label('Langue')->formatStateUsing(fn ($state) => $state === 'en' ? 'Anglais' : 'Français'),
                TextEntry::make('handled_at')->label('Traité le')->dateTime('d/m/Y H:i')->placeholder('Pas encore'),
                TextEntry::make('message')
                    ->label('Message')
                    ->columnSpanFull()
                    ->formatStateUsing(fn (string $state) => nl2br(e($state)))
                    ->html(),
            ]),
        ]);
    }
}
