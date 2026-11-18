<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?int $state) => Money::format((int) $state, 'fr');

        return $schema->columns(3)->components([
            Section::make('À vérifier')
                ->columnSpanFull()
                ->visible(fn (Order $record) => $record->needs_attention)
                ->schema([
                    TextEntry::make('admin_notes')->hiddenLabel()->color('danger'),
                ]),

            Section::make('Pièces')->columnSpan(2)->schema([
                RepeatableEntry::make('items')
                    ->hiddenLabel()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reference')->label('Réf.'),
                        TextEntry::make('title')->label('Titre'),
                        TextEntry::make('price_cents')->label('Prix')->formatStateUsing($money),
                    ]),
                TextEntry::make('shipping_cents')->label('Livraison')->formatStateUsing($money),
                TextEntry::make('total_cents')->label('Total payé')->formatStateUsing($money)->weight('bold'),
            ]),

            Section::make('Commande')->columnSpan(1)->schema([
                TextEntry::make('number')->label('Numéro'),
                TextEntry::make('status')->label('Statut')->badge(),
                TextEntry::make('paid_at')->label('Payée le')->dateTime('d/m/Y H:i'),
                TextEntry::make('shipped_at')->label('Expédiée le')->dateTime('d/m/Y')->placeholder('—'),
                TextEntry::make('locale')->label('Langue du client')->formatStateUsing(fn ($state) => $state === 'en' ? 'Anglais' : 'Français'),
            ]),

            Section::make('Client')->columnSpan(1)->schema([
                TextEntry::make('customer_name')->label('Nom'),
                TextEntry::make('customer_email')->label('E-mail')->copyable()
                    ->url(fn (Order $record) => 'mailto:'.$record->customer_email),
                TextEntry::make('customer_phone')->label('Téléphone')->placeholder('—'),
            ]),

            Section::make('Livraison')->columnSpan(1)->schema([
                TextEntry::make('address')
                    ->label('Adresse')
                    ->state(fn (Order $record) => $record->shippingAddressLines())
                    ->listWithLineBreaks(),
                TextEntry::make('shipping_method')->label('Mode'),
            ]),

            Section::make('Suivi')->columnSpan(1)->schema([
                TextEntry::make('carrier')->label('Transporteur')->placeholder('—'),
                TextEntry::make('tracking_number')->label('N° de suivi')->placeholder('—')->copyable(),
                TextEntry::make('tracking_url')->label('Lien')->placeholder('—')
                    ->url(fn (Order $record) => $record->tracking_url, shouldOpenInNewTab: true),
            ]),
        ]);
    }
}
