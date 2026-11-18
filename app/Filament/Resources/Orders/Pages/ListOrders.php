<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $years = Order::query()->whereNotNull('paid_at')->pluck('paid_at')
            ->map(fn ($d) => $d->year)->push(now()->year)->unique()->sortDesc()
            ->mapWithKeys(fn ($y) => [$y => (string) $y])->all();

        return [
            Action::make('ledger')
                ->label('Livre des recettes (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->schema([
                    Select::make('year')->label('Année')->options($years)->default(now()->year)->required(),
                ])
                ->action(fn (array $data) => $this->redirect(route('admin.exports.ledger', ['year' => $data['year']]))),
        ];
    }
}
