<x-mail::message>
# {{ __('shop.mail.confirmation_title') }}

{{ __('shop.mail.confirmation_intro', ['name' => $order->customer_name]) }}

<x-mail::table>
| {{ __('shop.mail.piece') }} | {{ __('shop.mail.price') }} |
|:--|--:|
@foreach ($order->items as $item)
| {{ $item->reference }} — {{ $item->title }} | {{ $item->formattedPrice($order->locale) }} |
@endforeach
| {{ __('shop.cart.shipping') }} | {{ $order->money('shipping_cents') }} |
| **{{ __('shop.cart.total') }}** | **{{ $order->money('total_cents') }}** |
</x-mail::table>

**{{ __('shop.mail.order_number') }}:** {{ $order->number }}

**{{ __('shop.mail.ship_to') }}:**<br>
{!! collect($order->shippingAddressLines())->map(fn ($l) => e($l))->implode('<br>') !!}

{{ __('shop.mail.confirmation_next') }}

{{ __('shop.mail.signature') }}<br>
{{ config('shop.name') }}
</x-mail::message>
