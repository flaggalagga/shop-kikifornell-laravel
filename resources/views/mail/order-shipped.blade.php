<x-mail::message>
# {{ __('shop.mail.shipped_title') }}

{{ __('shop.mail.shipped_intro', ['name' => $order->customer_name, 'number' => $order->number]) }}

@if ($order->carrier)
**{{ __('shop.mail.carrier') }}:** {{ $order->carrier }}
@endif

@if ($order->tracking_number)
**{{ __('shop.mail.tracking_number') }}:** {{ $order->tracking_number }}
@endif

@if ($order->tracking_url)
<x-mail::button :url="$order->tracking_url">
{{ __('shop.mail.track') }}
</x-mail::button>
@endif

{{ __('shop.mail.shipped_outro') }}

{{ __('shop.mail.signature') }}<br>
{{ config('shop.name') }}
</x-mail::message>
