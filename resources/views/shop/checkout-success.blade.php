<x-layout :title="__('shop.checkout.thanks_title')">
    <article class="text-page thanks">
        <h1>{{ __('shop.checkout.thanks_title') }}</h1>
        @if ($order)
            <p class="lede">{{ __('shop.checkout.thanks_order', ['number' => $order->number, 'email' => $order->customer_email]) }}</p>
        @else
            <p class="lede">{{ __('shop.checkout.thanks_pending') }}</p>
        @endif
        <p>{{ __('shop.checkout.thanks_next') }}</p>
        <a class="button button--outline" href="{{ route('home') }}">{{ __('shop.checkout.back_home') }}</a>
    </article>
</x-layout>
