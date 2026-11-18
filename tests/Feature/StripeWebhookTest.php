<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PieceStatus;
use App\Mail\NewOrderNotification;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Piece;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    private function reservedPiece(string $session = 'cs_1'): Piece
    {
        $piece = Piece::factory()->create(['price_cents' => 36000]);
        $piece->forceFill([
            'status' => PieceStatus::Reserved,
            'reserved_until' => now()->addMinutes(30),
            'reserved_by_session' => $session,
            'reservation_token' => 'tok',
        ])->save();

        return $piece;
    }

    public function test_rejects_events_with_a_bad_signature(): void
    {
        $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=forged',
        ], '{"type":"checkout.session.completed"}')->assertStatus(400);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_paid_session_creates_the_order_and_marks_pieces_sold(): void
    {
        Mail::fake();
        $piece = $this->reservedPiece();

        $this->postStripeEvent('checkout.session.completed', $this->paidSession('cs_1', [$piece->id]))->assertOk();

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('KF-'.now()->year.'-0001', $order->number);
        $this->assertSame(36900, $order->total_cents);
        $this->assertSame(900, $order->shipping_cents);
        $this->assertSame('Chamonix-Mont-Blanc', $order->shipping_city);
        $this->assertSame('pi_test_123', $order->stripe_payment_intent_id);
        $this->assertFalse($order->needs_attention);
        $this->assertSame($piece->reference, $order->items->sole()->reference);

        $piece->refresh();
        $this->assertSame(PieceStatus::Sold, $piece->status);
        $this->assertNotNull($piece->sold_at);
        $this->assertNull($piece->reserved_by_session);

        Mail::assertSent(OrderConfirmation::class, fn ($mail) => $mail->hasTo('marie@example.test') && $mail->locale === 'fr');
        Mail::assertSent(NewOrderNotification::class);
    }

    public function test_the_same_event_twice_creates_one_order(): void
    {
        Mail::fake();
        $piece = $this->reservedPiece();
        $session = $this->paidSession('cs_1', [$piece->id]);

        $this->postStripeEvent('checkout.session.completed', $session)->assertOk();
        $this->postStripeEvent('checkout.session.completed', $session)->assertOk();

        $this->assertDatabaseCount('orders', 1);
        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_order_numbers_follow_each_other(): void
    {
        Mail::fake();
        $a = $this->reservedPiece('cs_a');
        $b = $this->reservedPiece('cs_b');

        $this->postStripeEvent('checkout.session.completed', $this->paidSession('cs_a', [$a->id]));
        $this->postStripeEvent('checkout.session.completed', $this->paidSession('cs_b', [$b->id]));

        $this->assertSame(
            ['KF-'.now()->year.'-0001', 'KF-'.now()->year.'-0002'],
            Order::query()->orderBy('id')->pluck('number')->all(),
        );
    }

    public function test_payment_for_a_piece_no_longer_held_is_flagged(): void
    {
        Mail::fake();
        $piece = Piece::factory()->sold()->create();

        $this->postStripeEvent('checkout.session.completed', $this->paidSession('cs_late', [$piece->id]))->assertOk();

        $order = Order::query()->sole();
        $this->assertTrue($order->needs_attention);
        $this->assertStringContainsString($piece->reference, $order->admin_notes);
    }

    public function test_expired_session_puts_pieces_back_on_sale(): void
    {
        $piece = $this->reservedPiece('cs_x');

        $this->postStripeEvent('checkout.session.expired', ['id' => 'cs_x', 'object' => 'checkout.session'])->assertOk();

        $this->assertSame(PieceStatus::Available, $piece->fresh()->status);
    }

    public function test_expired_event_does_not_release_another_customers_reservation(): void
    {
        $piece = $this->reservedPiece('cs_new');

        $this->postStripeEvent('checkout.session.expired', ['id' => 'cs_old', 'object' => 'checkout.session'])->assertOk();

        $this->assertSame(PieceStatus::Reserved, $piece->fresh()->status);
    }

    public function test_slow_payment_holds_the_piece_until_it_settles(): void
    {
        Mail::fake();
        $piece = $this->reservedPiece('cs_sepa');

        $this->postStripeEvent('checkout.session.completed', $this->paidSession('cs_sepa', [$piece->id], ['payment_status' => 'unpaid']))->assertOk();
        $this->assertDatabaseCount('orders', 0);
        $this->assertTrue($piece->fresh()->reserved_until->greaterThan(now()->addDays(5)));

        $this->postStripeEvent('checkout.session.async_payment_succeeded', $this->paidSession('cs_sepa', [$piece->id]))->assertOk();
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(PieceStatus::Sold, $piece->fresh()->status);
    }

    public function test_full_refund_in_stripe_marks_the_order_refunded(): void
    {
        Mail::fake();
        $piece = $this->reservedPiece();
        $this->postStripeEvent('checkout.session.completed', $this->paidSession('cs_1', [$piece->id]));

        $this->postStripeEvent('charge.refunded', [
            'id' => 'ch_1', 'object' => 'charge', 'payment_intent' => 'pi_test_123', 'amount' => 36900, 'amount_refunded' => 36900,
        ])->assertOk();

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Refunded, $order->status);
        $this->assertTrue($order->needs_attention);
    }
}
