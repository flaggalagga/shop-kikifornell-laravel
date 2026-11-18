<?php

namespace App\Actions;

use App\Enums\PieceStatus;
use App\Models\Piece;
use App\Services\Payments\CheckoutGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Reserves the pieces and opens a Stripe checkout session.
 *
 * Reservation happens under a row lock, so two customers can never reserve
 * the same piece. The reservation outlives the Stripe session by a few
 * minutes; Stripe refuses payment once the session has expired.
 */
class StartCheckout
{
    private const RESERVATION_MARGIN_MINUTES = 5;

    public function __construct(private CheckoutGateway $gateway) {}

    /**
     * @param  array<int, int>  $pieceIds
     * @return array{id: string, url: string}
     *
     * @throws PiecesUnavailable
     */
    public function handle(array $pieceIds, string $zone, string $locale): array
    {
        $zoneConfig = config("shop.shipping.{$zone}");

        if (! $zoneConfig) {
            throw new InvalidArgumentException("Unknown shipping zone [{$zone}]");
        }

        $token = (string) Str::uuid();
        // Stripe needs at least 30 minutes; one extra minute absorbs clock drift.
        $sessionExpiresAt = now()->addMinutes(config('shop.reservation_minutes'))->addMinute();

        $pieces = DB::transaction(function () use ($pieceIds, $token, $sessionExpiresAt) {
            $pieces = Piece::query()->whereIn('id', $pieceIds)->lockForUpdate()->get();

            $unavailable = collect($pieceIds)
                ->diff($pieces->pluck('id'))
                ->merge($pieces->reject(fn (Piece $p) => $p->isPurchasable())->pluck('id'))
                ->values()
                ->all();

            if ($pieces->isEmpty() || $unavailable !== []) {
                throw new PiecesUnavailable($unavailable);
            }

            foreach ($pieces as $piece) {
                $piece->forceFill([
                    'status' => PieceStatus::Reserved,
                    'reserved_until' => $sessionExpiresAt->copy()->addMinutes(self::RESERVATION_MARGIN_MINUTES),
                    'reservation_token' => $token,
                    'reserved_by_session' => null,
                ])->save();
            }

            return $pieces;
        });

        try {
            $session = $this->gateway->createSession(
                $this->sessionParams($pieces, $zone, $zoneConfig, $locale, $token, $sessionExpiresAt->timestamp)
            );
        } catch (Throwable $e) {
            Piece::query()->where('reservation_token', $token)->get()->each->releaseReservation();

            throw $e;
        }

        Piece::query()->where('reservation_token', $token)->update(['reserved_by_session' => $session['id']]);

        return $session;
    }

    /**
     * @param  Collection<int, Piece>  $pieces
     * @return array<string, mixed>
     */
    private function sessionParams(Collection $pieces, string $zone, array $zoneConfig, string $locale, string $token, int $expiresAt): array
    {
        $currency = config('shop.currency');
        $publicImages = str_starts_with(config('app.url'), 'https://');
        $refs = $pieces->pluck('reference')->implode(', ');

        $metadata = [
            'reservation_token' => $token,
            'piece_ids' => $pieces->pluck('id')->implode(','),
            'shipping_zone' => $zone,
            'locale' => $locale,
        ];

        $params = [
            'mode' => 'payment',
            'locale' => $locale,
            'expires_at' => $expiresAt,
            'line_items' => $pieces->map(fn (Piece $piece) => [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $piece->price_cents,
                    'product_data' => array_filter([
                        'name' => $piece->reference.' — '.$piece->localized('title', $locale),
                        'images' => $publicImages && $piece->mainPhoto() ? [$piece->mainPhoto()->url(960)] : null,
                        'metadata' => ['piece_id' => (string) $piece->id],
                    ]),
                ],
            ])->values()->all(),
            'shipping_address_collection' => ['allowed_countries' => $zoneConfig['countries']],
            'shipping_options' => [[
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'fixed_amount' => ['amount' => $zoneConfig['price'], 'currency' => $currency],
                    'display_name' => $zoneConfig['method'][$locale] ?? $zoneConfig['method']['fr'],
                    'delivery_estimate' => [
                        'minimum' => ['unit' => 'business_day', 'value' => $zoneConfig['days'][0]],
                        'maximum' => ['unit' => 'business_day', 'value' => $zoneConfig['days'][1]],
                    ],
                ],
            ]],
            'phone_number_collection' => ['enabled' => true],
            'success_url' => route('checkout.success', ['locale' => $locale]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancelled', ['locale' => $locale]),
            'metadata' => $metadata,
            'payment_intent_data' => [
                'description' => config('shop.name').' — '.$refs,
                'metadata' => $metadata,
            ],
        ];

        $termsLink = route('pages.show', ['locale' => $locale, 'page' => 'cgv']);
        $termsText = __('shop.checkout.terms_notice', ['url' => $termsLink], $locale);

        if (config('shop.stripe.require_terms')) {
            $params['consent_collection'] = ['terms_of_service' => 'required'];
            $params['custom_text'] = ['terms_of_service_acceptance' => ['message' => $termsText]];
        } else {
            $params['custom_text'] = ['submit' => ['message' => $termsText]];
        }

        return $params;
    }
}
