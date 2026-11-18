@php use App\Support\Money; @endphp
<x-layout :title="__('shop.cart.title')">
    <div class="cart">
        <h1>{{ __('shop.cart.title') }}</h1>

        @if ($pieces->isEmpty())
            <p class="empty">{{ __('shop.cart.empty') }}</p>
            <a class="button" href="{{ route('pieces.index') }}">{{ __('shop.home.cta') }}</a>
        @else
            <ul class="cart__items">
                @foreach ($pieces as $piece)
                    <li class="cart-item @unless ($piece->isPurchasable()) cart-item--unavailable @endunless">
                        <a href="{{ route('pieces.show', $piece) }}" class="cart-item__image">
                            @if ($photo = $piece->mainPhoto())
                                <img src="{{ $photo->url(480) }}" alt="" loading="lazy">
                            @endif
                        </a>
                        <div class="cart-item__text">
                            <p class="cart-item__ref">{{ $piece->reference }}</p>
                            <p class="cart-item__title"><a href="{{ route('pieces.show', $piece) }}">{{ $piece->localized('title') }}</a></p>
                            @unless ($piece->isPurchasable())
                                <p class="form__error">{{ __('shop.cart.no_longer_available') }}</p>
                            @endunless
                        </div>
                        <p class="cart-item__price">{{ $piece->formattedPrice() }}</p>
                        <form method="post" action="{{ route('cart.remove', $piece->id) }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="link-button">{{ __('shop.cart.remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <div class="cart__summary">
                <form method="post" action="{{ route('cart.zone') }}" class="destination" data-autosubmit>
                    @csrf
                    <fieldset>
                        <legend>{{ __('shop.cart.destination') }}</legend>
                        @foreach (config('shop.shipping') as $key => $option)
                            <label class="destination__option">
                                <input type="radio" name="zone" value="{{ $key }}" @checked($zone === $key)>
                                <span>
                                    <span class="destination__label">{{ $option['label'][app()->getLocale()] }}</span>
                                    <span class="destination__method">{{ $option['method'][app()->getLocale()] }}, {{ Money::format($option['price']) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                    <noscript><button type="submit" class="button button--quiet">{{ __('shop.cart.update_destination') }}</button></noscript>
                </form>

                <dl class="totals">
                    <div><dt>{{ __('shop.cart.subtotal') }}</dt><dd>{{ Money::format($subtotal) }}</dd></div>
                    <div><dt>{{ __('shop.cart.shipping') }}</dt><dd>{{ $shipping === null ? __('shop.cart.choose_destination') : Money::format($shipping) }}</dd></div>
                    <div class="totals__total"><dt>{{ __('shop.cart.total') }}</dt><dd>{{ Money::format($subtotal + (int) $shipping) }}</dd></div>
                </dl>
                @if (config('shop.legal.vat_note'))
                    <p class="cart__vat">{{ config('shop.legal.vat_note') }}</p>
                @endif

                <form method="post" action="{{ route('checkout.start') }}">
                    @csrf
                    <button class="button button--wide" type="submit" @disabled(! $canCheckout)>{{ __('shop.cart.checkout') }}</button>
                </form>
                <p class="cart__note">{{ __('shop.cart.checkout_note', ['minutes' => config('shop.reservation_minutes')]) }}</p>
            </div>
        @endif
    </div>
</x-layout>
