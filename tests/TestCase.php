<?php

namespace Tests;

use App\Services\Payments\CheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeCheckoutGateway;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected FakeCheckoutGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->gateway = new FakeCheckoutGateway;
        $this->app->instance(CheckoutGateway::class, $this->gateway);
    }

    /** POST a Stripe event to the webhook with a valid signature. */
    protected function postStripeEvent(string $type, array $object): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $object],
        ]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", config('shop.stripe.webhook_secret'));

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }

    /** A paid Checkout Session payload as Stripe sends it. */
    protected function paidSession(string $id, array $pieceIds, array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => $id,
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_subtotal' => 36000,
            'amount_total' => 36900,
            'currency' => 'eur',
            'payment_intent' => 'pi_test_123',
            'shipping_cost' => ['amount_total' => 900],
            'customer_details' => [
                'name' => 'Marie Curie',
                'email' => 'marie@example.test',
                'phone' => '+33600000000',
                'address' => ['country' => 'FR'],
            ],
            'collected_information' => [
                'shipping_details' => [
                    'name' => 'Marie Curie',
                    'address' => [
                        'line1' => '1 rue du Lac',
                        'line2' => null,
                        'postal_code' => '74400',
                        'city' => 'Chamonix-Mont-Blanc',
                        'state' => null,
                        'country' => 'FR',
                    ],
                ],
            ],
            'metadata' => [
                'piece_ids' => implode(',', $pieceIds),
                'shipping_zone' => 'france',
                'locale' => 'fr',
                'reservation_token' => 'tok',
            ],
        ], $overrides);
    }
}
