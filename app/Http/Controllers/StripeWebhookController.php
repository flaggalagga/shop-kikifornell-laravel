<?php

namespace App\Http\Controllers;

use App\Actions\FulfillCheckout;
use App\Actions\ReleaseCheckout;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Payments\CheckoutGateway;
use App\Services\Payments\InvalidWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Stripe events. Configure in Stripe Dashboard > Developers >
 * Webhooks with the events listed in README.md.
 */
class StripeWebhookController
{
    public function __invoke(
        Request $request,
        CheckoutGateway $gateway,
        FulfillCheckout $fulfill,
        ReleaseCheckout $release,
    ): JsonResponse {
        try {
            $event = $gateway->parseWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (InvalidWebhook $e) {
            Log::warning('Rejected Stripe webhook', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $object = $event['data']['object'] ?? [];

        switch ($event['type'] ?? null) {
            case 'checkout.session.completed':
                if (($object['payment_status'] ?? null) === 'unpaid') {
                    $release->holdForPendingPayment($object['id']);
                } else {
                    $fulfill->handle($object);
                }
                break;

            case 'checkout.session.async_payment_succeeded':
                $fulfill->handle($object);
                break;

            case 'checkout.session.expired':
            case 'checkout.session.async_payment_failed':
                $release->handle($object['id']);
                break;

            case 'charge.refunded':
                $this->markRefunded($object);
                break;
        }

        return response()->json(['received' => true]);
    }

    /** @param array<string, mixed> $charge */
    private function markRefunded(array $charge): void
    {
        $order = Order::query()->where('stripe_payment_intent_id', $charge['payment_intent'] ?? '')->first();

        if (! $order) {
            return;
        }

        $full = ($charge['amount_refunded'] ?? 0) >= ($charge['amount'] ?? PHP_INT_MAX);

        if ($full && $order->status === OrderStatus::Refunded) {
            return; // Same event delivered twice.
        }

        $note = $full
            ? 'Remboursée intégralement dans Stripe le '.now()->format('d/m/Y').'. Remettre la pièce en vente si elle a été retournée.'
            : 'Remboursement partiel dans Stripe le '.now()->format('d/m/Y').'.';

        $order->forceFill([
            'status' => $full ? OrderStatus::Refunded : $order->status,
            'needs_attention' => true,
            'admin_notes' => trim(($order->admin_notes ? $order->admin_notes."\n" : '').$note),
        ])->save();
    }
}
