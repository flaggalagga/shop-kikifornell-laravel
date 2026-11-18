<?php

namespace App\Services\Payments;

/**
 * The only door to the payment provider. Card data never touches this app:
 * customers pay on Stripe's hosted checkout page.
 */
interface CheckoutGateway
{
    /**
     * Create a hosted checkout session.
     *
     * @param  array<string, mixed>  $params  Stripe Checkout Session parameters
     * @return array{id: string, url: string}
     */
    public function createSession(array $params): array;

    /** Expire an open session so it can no longer be paid. Safe to call twice. */
    public function expireSession(string $sessionId): void;

    /**
     * Verify the webhook signature and return the event as an array.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidWebhook
     */
    public function parseWebhook(string $payload, ?string $signature): array;
}
