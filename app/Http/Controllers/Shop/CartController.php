<?php

namespace App\Http\Controllers\Shop;

use App\Models\Piece;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController
{
    public function show(Cart $cart): View
    {
        $pieces = $cart->pieces();
        $zone = $cart->zone();
        $subtotal = (int) $pieces->filter->isPurchasable()->sum('price_cents');
        $shipping = $zone ? (int) config("shop.shipping.{$zone}.price") : null;

        return view('shop.cart', [
            'pieces' => $pieces,
            'zone' => $zone,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'canCheckout' => $pieces->isNotEmpty() && $pieces->every->isPurchasable() && $zone !== null,
        ]);
    }

    public function add(Piece $piece, Cart $cart): RedirectResponse
    {
        if (! $piece->isPurchasable()) {
            return back()->with('notice', __('shop.cart.not_available', ['ref' => $piece->reference]));
        }

        $cart->add($piece);

        return redirect()->route('cart.show')->with('notice', __('shop.cart.added', ['title' => $piece->localized('title')]));
    }

    public function remove(int $pieceId, Cart $cart): RedirectResponse
    {
        $cart->remove($pieceId);

        return redirect()->route('cart.show');
    }

    public function zone(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'zone' => ['required', Rule::in(array_keys(config('shop.shipping')))],
        ]);

        $cart->setZone($data['zone']);

        return redirect()->route('cart.show');
    }
}
