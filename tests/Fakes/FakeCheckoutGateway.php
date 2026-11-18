<?php

namespace Tests\Fakes;

use App\Services\Payments\CheckoutGateway;
use App\Services\Payments\StripeCheckoutGateway;
use RuntimeException;

/** Records checkout sessions instead of calling Stripe. Webhooks use real signature checks. */
class FakeCheckoutGateway implements CheckoutGateway
{
    /** @var array<int, array<string, mixed>> */
    public array $created = [];

    /** @var array<int, string> */
    public array $expired = [];

    public bool $failNext = false;

    public function createSession(array $params): array
    {
        if ($this->failNext) {
            throw new RuntimeException('Stripe is down');
        }

        $id = 'cs_test_'.(count($this->created) + 1);
        $this->created[] = ['id' => $id, ...$params];

        return ['id' => $id, 'url' => 'https://checkout.stripe.test/'.$id];
    }

    public function expireSession(string $sessionId): void
    {
        $this->expired[] = $sessionId;
    }

    public function parseWebhook(string $payload, ?string $signature): array
    {
        return (new StripeCheckoutGateway(null, config('shop.stripe.webhook_secret')))->parseWebhook($payload, $signature);
    }
}
