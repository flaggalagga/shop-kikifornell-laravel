<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PieceStatus;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Piece;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $paid = Order::query()->whereNotIn('status', [OrderStatus::Refunded, OrderStatus::Cancelled]);

        return [
            Stat::make('À expédier', Order::query()->where('status', OrderStatus::Paid)->count())
                ->description('Commandes payées en attente d’envoi')
                ->color('warning'),
            Stat::make('Messages non traités', Inquiry::query()->whereNull('handled_at')->count()),
            Stat::make('Pièces en vente', Piece::query()->where('status', PieceStatus::Available)->count()),
            Stat::make('Ventes ce mois-ci', Money::format((int) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total_cents'), 'fr')),
            Stat::make('Ventes '.now()->year, Money::format((int) (clone $paid)->whereYear('paid_at', now()->year)->sum('total_cents'), 'fr')),
        ];
    }
}
