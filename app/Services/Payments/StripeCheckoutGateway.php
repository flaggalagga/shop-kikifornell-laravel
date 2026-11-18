<?php

namespace App\Services\Payments;

use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeCheckoutGateway implements CheckoutGateway
{
    public function __construct(
        private ?string $secretKey,
        private ?string $webhookSecret,
    ) {}

    private function client(): StripeClient
    {
        if (blank($this->secretKey)) {
            throw new \RuntimeException('STRIPE_SECRET is not set in .env');
        }

        return new StripeClient($this->secretKey);
    }

    public function createSession(array $params): array
    {
        $session = $this->client()->checkout->sessions->create($params);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function expireSession(string $sessionId): void
    {
        try {
            $this->client()->checkout->sessions->expire($sessionId);
        } catch (InvalidRequestException) {
            // Already expired or already paid: nothing to do.
        }
    }

    public function parseWebhook(string $payload, ?string $signature): array
    {
        if (blank($this->webhookSecret)) {
            throw new InvalidWebhook('STRIPE_WEBHOOK_SECRET is not set in .env');
        }

        try {
            return Webhook::constructEvent($payload, (string) $signature, $this->webhookSecret)->toArray();
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            throw new InvalidWebhook($e->getMessage(), previous: $e);
        }
    }
}
