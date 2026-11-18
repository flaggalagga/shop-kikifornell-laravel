<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderActions;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('items'))
            ->columns([
                IconColumn::make('needs_attention')
                    ->label('')
                    ->icon(fn (bool $state) => $state ? 'heroicon-o-exclamation-triangle' : null)
                    ->color('danger')
                    ->tooltip(fn (Order $record) => $record->needs_attention ? $record->admin_notes : null),
                TextColumn::make('number')->label('N°')->searchable()->sortable(),
                TextColumn::make('paid_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer_name')
                    ->label('Client')
                    ->searchable(['customer_name', 'customer_email'])
                    ->description(fn (Order $record) => $record->items->pluck('reference')->implode(', ')),
                TextColumn::make('shipping_country')->label('Pays'),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state) => Money::format($state, 'fr'))
                    ->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(OrderStatus::class),
                TernaryFilter::make('needs_attention')->label('À vérifier'),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    OrderActions::markShipped(),
                    OrderActions::markDelivered(),
                    OrderActions::invoice(),
                    OrderActions::packingSlip(),
                    OrderActions::openInStripe(),
                ]),
            ]);
    }
}
