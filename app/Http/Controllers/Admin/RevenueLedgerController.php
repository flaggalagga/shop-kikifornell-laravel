<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Livre des recettes (micro-entreprise income ledger) as CSV, one line per
 * paid order, for a given year. Semicolon-separated with a UTF-8 BOM so it
 * opens cleanly in French Excel / LibreOffice.
 */
class RevenueLedgerController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $year = (int) $request->query('year', now()->year);

        $orders = Order::query()
            ->with('items')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $year)
            ->orderBy('paid_at')
            ->get();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date d’encaissement', 'N° de facture', 'Client', 'Nature de la vente', 'Bijoux (€)', 'Livraison (€)', 'Montant encaissé (€)', 'Mode de règlement', 'Statut'], ';');

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->paid_at->format('d/m/Y'),
                    $order->number,
                    $order->customer_name,
                    $order->items->map(fn ($i) => "{$i->reference} {$i->title}")->implode(', '),
                    Money::plain($order->subtotal_cents),
                    Money::plain($order->shipping_cents),
                    Money::plain($order->total_cents),
                    'Carte bancaire (Stripe)',
                    $order->status->getLabel(),
                ], ';');
            }

            fclose($out);
        }, "livre-des-recettes-{$year}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
