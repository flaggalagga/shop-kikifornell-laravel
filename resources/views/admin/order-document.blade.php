@php
    use App\Support\Money;
    $legal = config('shop.legal');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $isInvoice ? 'Facture' : 'Bon de livraison' }} {{ $order->number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: "Helvetica Neue", Arial, sans-serif; font-size: 10.5pt; color: #1b2a3b; line-height: 1.45; max-width: 180mm; margin: 2rem auto; }
        h1 { font-size: 20pt; font-weight: 400; margin: 0 0 4mm; }
        .head { display: flex; justify-content: space-between; gap: 10mm; margin-bottom: 12mm; }
        .seller strong { font-size: 13pt; }
        .meta { text-align: right; }
        .parties { display: flex; gap: 10mm; margin-bottom: 10mm; }
        .parties div { flex: 1; }
        .label { color: #59677a; font-size: 9pt; margin: 0 0 1mm; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
        th, td { text-align: left; padding: 2.5mm 0; border-bottom: 1px solid #dfe7ea; vertical-align: top; }
        th { font-weight: 400; color: #59677a; font-size: 9pt; }
        td.num, th.num { text-align: right; }
        tr.total td { font-weight: 700; border-bottom: 0; border-top: 1.5px solid #1b2a3b; }
        .note { color: #59677a; font-size: 9pt; }
        .print { position: fixed; top: 1rem; right: 1rem; }
        @media print { .print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="print" onclick="window.print()">Imprimer / PDF</button>

    <div class="head">
        <div class="seller">
            <strong>{{ config('shop.name') }}</strong><br>
            {{ $legal['company_name'] }}<br>
            {{ $legal['address'] }}<br>
            SIRET {{ $legal['siret'] }}
            @if ($legal['vat_number'])<br>TVA {{ $legal['vat_number'] }}@endif
            <br>{{ config('shop.contact_email') }}
        </div>
        <div class="meta">
            <h1>{{ $isInvoice ? 'Facture' : 'Bon de livraison' }}</h1>
            N° {{ $order->number }}<br>
            Date : {{ $order->paid_at?->format('d/m/Y') }}
        </div>
    </div>

    <div class="parties">
        <div>
            <p class="label">Client</p>
            {{ $order->customer_name }}<br>
            {{ $order->customer_email }}
            @if ($order->customer_phone)<br>{{ $order->customer_phone }}@endif
        </div>
        <div>
            <p class="label">Adresse de livraison</p>
            @foreach ($order->shippingAddressLines() as $line){{ $line }}<br>@endforeach
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Désignation</th>
                <th class="num">Qté</th>
                @if ($isInvoice)<th class="num">Prix</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->reference }}</td>
                    <td>{{ $item->title }} — pièce unique</td>
                    <td class="num">1</td>
                    @if ($isInvoice)<td class="num">{{ Money::format($item->price_cents, 'fr') }}</td>@endif
                </tr>
            @endforeach
            @if ($isInvoice)
                <tr>
                    <td></td>
                    <td>Livraison — {{ $order->shipping_method }}</td>
                    <td class="num">1</td>
                    <td class="num">{{ Money::format($order->shipping_cents, 'fr') }}</td>
                </tr>
                <tr class="total">
                    <td></td>
                    <td>Total TTC</td>
                    <td></td>
                    <td class="num">{{ Money::format($order->total_cents, 'fr') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if ($isInvoice)
        @if ($legal['vat_note'])<p>{{ $legal['vat_note'] }}</p>@endif
        <p class="note">Payée par carte bancaire (Stripe) le {{ $order->paid_at?->format('d/m/Y') }}.</p>
    @else
        <p class="note">Merci pour votre commande. Pour toute question : {{ config('shop.contact_email') }}</p>
    @endif
</body>
</html>
