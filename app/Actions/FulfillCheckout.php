<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PieceStatus;
use App\Mail\NewOrderNotification;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Piece;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Turns a paid Stripe checkout session into an order and marks the pieces
 * sold. Idempotent: Stripe may deliver the same event more than once.
 */
class FulfillCheckout
{
    /** @param array<string, mixed> $session Stripe Checkout Session object */
    public function handle(array $session): ?Order
    {
        $sessionId = $session['id'];

        if (! in_array($session['payment_status'] ?? null, ['paid', 'no_payment_required'], true)) {
            return null;
        }

        if ($existing = Order::query()->where('stripe_checkout_session_id', $sessionId)->first()) {
            return $existing;
        }

        $metadata = $session['metadata'] ?? [];
        $pieceIds = array_filter(array_map('intval', explode(',', (string) ($metadata['piece_ids'] ?? ''))));
        $zone = (string) ($metadata['shipping_zone'] ?? '');
        $locale = in_array($metadata['locale'] ?? null, config('shop.locales'), true) ? $metadata['locale'] : 'fr';

        [$order, $created] = DB::transaction(function () use ($session, $sessionId, $pieceIds, $zone, $locale, $metadata) {
            if ($existing = Order::query()->where('stripe_checkout_session_id', $sessionId)->lockForUpdate()->first()) {
                return [$existing, false];
            }

            $pieces = Piece::query()->whereIn('id', $pieceIds)->lockForUpdate()->get()->keyBy('id');
            $warnings = [];

            foreach ($pieceIds as $id) {
                $piece = $pieces->get($id);

                if (! $piece) {
                    $warnings[] = "La pièce #{$id} n'existe plus.";

                    continue;
                }

                $heldByThisCheckout = $piece->status === PieceStatus::Reserved
                    && ($piece->reserved_by_session === $sessionId
                        || $piece->reservation_token === ($metadata['reservation_token'] ?? null));

                if (! $heldByThisCheckout) {
                    $warnings[] = "{$piece->reference} était « {$piece->status->getLabel()} » au moment du paiement : vérifier qu'elle n'a pas été vendue deux fois.";
                }
            }

            $details = $session['customer_details'] ?? [];
            $shipping = $session['collected_information']['shipping_details'] ?? $session['shipping_details'] ?? [];
            $address = $shipping['address'] ?? $details['address'] ?? [];
            $paymentIntent = $session['payment_intent'] ?? null;
            $subtotal = (int) ($session['amount_subtotal'] ?? 0);
            $total = (int) ($session['amount_total'] ?? 0);

            $order = Order::create([
                'number' => Order::nextNumber(),
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => is_array($paymentIntent) ? ($paymentIntent['id'] ?? null) : $paymentIntent,
                'status' => OrderStatus::Paid,
                'needs_attention' => $warnings !== [],
                'locale' => $locale,
                'customer_name' => $details['name'] ?? ($shipping['name'] ?? null),
                'customer_email' => $details['email'] ?? '',
                'customer_phone' => $details['phone'] ?? null,
                'shipping_name' => $shipping['name'] ?? ($details['name'] ?? null),
                'shipping_line1' => $address['line1'] ?? null,
                'shipping_line2' => $address['line2'] ?? null,
                'shipping_postal_code' => $address['postal_code'] ?? null,
                'shipping_city' => $address['city'] ?? null,
                'shipping_state' => $address['state'] ?? null,
                'shipping_country' => $address['country'] ?? null,
                'shipping_zone' => $zone ?: null,
                'shipping_method' => config("shop.shipping.{$zone}.method.fr"),
                'subtotal_cents' => $subtotal,
                'shipping_cents' => (int) ($session['shipping_cost']['amount_total'] ?? max(0, $total - $subtotal)),
                'total_cents' => $total,
                'currency' => $session['currency'] ?? config('shop.currency'),
                'paid_at' => now(),
                'admin_notes' => $warnings ? implode("\n", $warnings) : null,
            ]);

            foreach ($pieceIds as $id) {
                if (! $piece = $pieces->get($id)) {
                    continue;
                }

                $order->items()->create([
                    'piece_id' => $piece->id,
                    'reference' => $piece->reference,
                    'title' => $piece->localized('title', $locale),
                    'price_cents' => $piece->price_cents,
                ]);

                $piece->forceFill([
                    'status' => PieceStatus::Sold,
                    'sold_at' => now(),
                    'reserved_until' => null,
                    'reservation_token' => null,
                    'reserved_by_session' => null,
                ])->save();
            }

            return [$order, true];
        });

        if ($created) {
            $this->sendEmails($order);
        }

        return $order;
    }

    private function sendEmails(Order $order): void
    {
        try {
            if (filled($order->customer_email)) {
                Mail::to($order->customer_email)->locale($order->locale)->send(new OrderConfirmation($order));
            }
            Mail::to(config('shop.contact_email'))->locale('fr')->send(new NewOrderNotification($order));
        } catch (Throwable $e) {
            // The order is saved; a mail failure must not make Stripe retry.
            Log::error('Order emails failed', ['order' => $order->number, 'error' => $e->getMessage()]);
        }
    }
}
