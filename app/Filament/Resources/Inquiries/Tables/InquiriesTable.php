<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Filament\Resources\Inquiries\InquiriesActions;
use App\Models\Inquiry;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('handled_at')->label('Traité')->boolean()->state(fn (Inquiry $r) => $r->handled_at !== null),
                TextColumn::make('created_at')->label('Reçu')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('name')->label('Nom')->searchable(['name', 'email'])->description(fn (Inquiry $r) => $r->email),
                TextColumn::make('piece.reference')->label('Pièce')->placeholder('—'),
                TextColumn::make('message')->label('Message')->limit(80)->wrap(),
            ])
            ->filters([
                TernaryFilter::make('handled_at')->label('Traité')->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                InquiriesActions::markHandled(),
            ]);
    }
}
