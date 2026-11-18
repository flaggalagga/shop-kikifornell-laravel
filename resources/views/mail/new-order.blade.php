<x-mail::message>
# Nouvelle commande {{ $order->number }}

@if ($order->needs_attention)
<x-mail::panel>
**À vérifier :** {{ $order->admin_notes }}
</x-mail::panel>
@endif

**Client :** {{ $order->customer_name }} — {{ $order->customer_email }} {{ $order->customer_phone ? '— '.$order->customer_phone : '' }}

**Livraison :** {{ $order->shipping_method }}<br>
{!! collect($order->shippingAddressLines())->map(fn ($l) => e($l))->implode('<br>') !!}

<x-mail::table>
| Pièce | Prix |
|:--|--:|
@foreach ($order->items as $item)
| {{ $item->reference }} — {{ $item->title }} | {{ $item->formattedPrice('fr') }} |
@endforeach
| Livraison | {{ \App\Support\Money::format($order->shipping_cents, 'fr') }} |
| **Total** | **{{ \App\Support\Money::format($order->total_cents, 'fr') }}** |
</x-mail::table>

<x-mail::button :url="url('/admin/orders')">
Ouvrir les commandes
</x-mail::button>
</x-mail::message>
