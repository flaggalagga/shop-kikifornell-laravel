<?php

namespace Tests\Feature;

use App\Enums\PieceStatus;
use App\Models\Piece;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    private function cartWith(Piece ...$pieces): void
    {
        foreach ($pieces as $piece) {
            $this->post("/fr/cart/{$piece->slug}")->assertRedirect('/fr/cart');
        }
        $this->post('/fr/cart/destination', ['zone' => 'france'])->assertRedirect('/fr/cart');
    }

    public function test_cart_shows_pieces_and_shipping_total(): void
    {
        $piece = Piece::factory()->create(['price_cents' => 54000]);
        $this->cartWith($piece);

        $this->get('/fr/cart')
            ->assertOk()
            ->assertSee($piece->reference)
            ->assertSee("549\u{a0}€"); // 540 € + 9 € shipping (formatted with a non-breaking space)
    }

    public function test_checkout_reserves_pieces_and_redirects_to_stripe(): void
    {
        $piece = Piece::factory()->create(['price_cents' => 36000]);
        $this->cartWith($piece);

        $this->post('/fr/checkout')->assertRedirect('https://checkout.stripe.test/cs_test_1');

        $piece->refresh();
        $this->assertSame(PieceStatus::Reserved, $piece->status);
        $this->assertSame('cs_test_1', $piece->reserved_by_session);
        $this->assertTrue($piece->reserved_until->isFuture());

        $params = $this->gateway->created[0];
        $this->assertSame('payment', $params['mode']);
        $this->assertSame('fr', $params['locale']);
        $this->assertSame(36000, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(['FR', 'MC'], $params['shipping_address_collection']['allowed_countries']);
        $this->assertSame(900, $params['shipping_options'][0]['shipping_rate_data']['fixed_amount']['amount']);
        $this->assertSame((string) $piece->id, $params['metadata']['piece_ids']);
        // Stripe requires the session to live at least 30 minutes.
        $this->assertGreaterThanOrEqual(now()->addMinutes(30)->timestamp, $params['expires_at']);
    }

    public function test_a_reserved_piece_cannot_be_bought_by_someone_else(): void
    {
        $piece = Piece::factory()->create();
        $piece->forceFill(['status' => PieceStatus::Reserved, 'reserved_until' => now()->addMinutes(20)])->save();

        $this->post("/fr/cart/{$piece->slug}")->assertSessionHas('notice');
        $this->assertCount(0, $this->gateway->created);
    }

    public function test_second_buyer_is_turned_away_once_first_reserved(): void
    {
        $piece = Piece::factory()->create();
        $this->cartWith($piece);

        // Another customer reserves it between "add to cart" and "pay".
        $piece->forceFill(['status' => PieceStatus::Reserved, 'reserved_until' => now()->addMinutes(20), 'reserved_by_session' => 'cs_other'])->save();

        $this->post('/fr/checkout')->assertRedirect('/fr/cart')->assertSessionHas('notice');
        $this->assertCount(0, $this->gateway->created);
        $this->assertSame('cs_other', $piece->fresh()->reserved_by_session);
    }

    public function test_an_expired_reservation_is_for_sale_again(): void
    {
        $piece = Piece::factory()->create();
        $this->cartWith($piece);
        $piece->forceFill(['status' => PieceStatus::Reserved, 'reserved_until' => now()->subMinute(), 'reserved_by_session' => 'cs_old'])->save();

        $this->post('/fr/checkout')->assertRedirect('https://checkout.stripe.test/cs_test_1');
        $this->assertSame('cs_test_1', $piece->fresh()->reserved_by_session);
    }

    public function test_reservation_is_released_if_stripe_fails(): void
    {
        $piece = Piece::factory()->create();
        $this->cartWith($piece);
        $this->gateway->failNext = true;

        $this->post('/fr/checkout')->assertRedirect('/fr/cart')->assertSessionHas('notice');
        $this->assertSame(PieceStatus::Available, $piece->fresh()->status);
    }

    public function test_cancelling_on_stripe_frees_the_pieces_at_once(): void
    {
        $piece = Piece::factory()->create();
        $this->cartWith($piece);
        $this->post('/fr/checkout');

        $this->get('/fr/checkout/cancelled')->assertRedirect('/fr/cart');

        $this->assertSame(['cs_test_1'], $this->gateway->expired);
        $this->assertSame(PieceStatus::Available, $piece->fresh()->status);
        $this->get('/fr/cart')->assertSee($piece->reference);
    }

    public function test_checkout_needs_a_destination(): void
    {
        $piece = Piece::factory()->create();
        $this->post("/fr/cart/{$piece->slug}");

        $this->post('/fr/checkout')->assertRedirect('/fr/cart');
        $this->assertCount(0, $this->gateway->created);
    }

    public function test_success_page_clears_the_cart(): void
    {
        $piece = Piece::factory()->create();
        $this->cartWith($piece);

        $this->get('/fr/checkout/thank-you?session_id=cs_unknown')->assertOk()->assertSee('Merci');
        $this->get('/fr/cart')->assertSee('Votre panier est vide');
    }
}
