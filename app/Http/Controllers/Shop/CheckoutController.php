<?php

namespace App\Http\Controllers\Shop;

use App\Actions\PiecesUnavailable;
use App\Actions\ReleaseCheckout;
use App\Actions\StartCheckout;
use App\Models\Order;
use App\Models\Piece;
use App\Services\Payments\CheckoutGateway;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class CheckoutController
{
    public function start(Cart $cart, StartCheckout $checkout): RedirectResponse
    {
        if ($cart->ids() === [] || ! $cart->zone()) {
            return redirect()->route('cart.show');
        }

        try {
            $session = $checkout->handle($cart->ids(), $cart->zone(), app()->getLocale());
        } catch (PiecesUnavailable $e) {
            $refs = Piece::query()->whereIn('id', $e->pieceIds)->pluck('reference')->implode(', ');

            return redirect()->route('cart.show')
                ->with('notice', __('shop.checkout.unavailable', ['refs' => $refs ?: '—']));
        } catch (Throwable $e) {
            Log::error('Checkout could not start', ['error' => $e->getMessage()]);

            return redirect()->route('cart.show')->with('notice', __('shop.checkout.failed'));
        }

        $cart->rememberCheckoutSession($session['id']);

        return redirect()->away($session['url'], 303);
    }

    public function success(Request $request, Cart $cart): View
    {
        $sessionId = (string) $request->query('session_id');
        $cart->clear();

        return view('shop.checkout-success', [
            // The Stripe webhook may arrive a few seconds after the customer.
            'order' => $sessionId ? Order::query()->where('stripe_checkout_session_id', $sessionId)->first() : null,
        ]);
    }

    /** Customer clicked "back" on Stripe: free the pieces right away. */
    public function cancelled(Cart $cart, CheckoutGateway $gateway, ReleaseCheckout $release): RedirectResponse
    {
        if ($sessionId = $cart->lastCheckoutSession()) {
            try {
                $gateway->expireSession($sessionId);
            } catch (Throwable $e) {
                Log::warning('Could not expire Stripe session', ['session' => $sessionId, 'error' => $e->getMessage()]);
            }
            $release->handle($sessionId);
            $cart->forgetCheckoutSession();
        }

        return redirect()->route('cart.show')->with('notice', __('shop.checkout.cancelled'));
    }
}
