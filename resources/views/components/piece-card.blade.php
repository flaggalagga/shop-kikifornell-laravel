@props(['piece', 'sizes' => '(min-width: 64rem) 30vw, (min-width: 40rem) 45vw, 90vw'])
@php
    $photo = $piece->mainPhoto();
    $status = $piece->publicStatus();
@endphp
<article class="piece-card">
    <a href="{{ route('pieces.show', $piece) }}" class="piece-card__link">
        <div class="piece-card__image">
            @if ($photo)
                <img src="{{ $photo->url(960) }}" srcset="{{ $photo->srcset() }}" sizes="{{ $sizes }}"
                     alt="{{ $photo->altText() }}" loading="lazy" decoding="async"
                     @if ($photo->width) width="{{ $photo->width }}" height="{{ $photo->height }}" @endif>
            @endif
        </div>
        <p class="piece-card__ref">{{ $piece->reference }}</p>
        <h3 class="piece-card__title">{{ $piece->localized('title') }}</h3>
    </a>
    <p class="piece-card__price">
        @if ($status === \App\Enums\PieceStatus::Sold)
            <span class="status status--sold">{{ __('shop.status.sold') }}</span>
        @elseif ($status === \App\Enums\PieceStatus::Reserved)
            {{ $piece->formattedPrice() }} <span class="status status--reserved">{{ __('shop.status.reserved') }}</span>
        @else
            {{ $piece->formattedPrice() }}
        @endif
    </p>
</article>
