<x-layout>
    <section class="hero">
        @if ($hero && ($heroPhoto = $hero->mainPhoto()))
            <a class="hero__image" href="{{ route('pieces.show', $hero) }}">
                <img src="{{ $heroPhoto->url(1600) }}" srcset="{{ $heroPhoto->srcset() }}" sizes="(min-width: 64rem) 58vw, 100vw"
                     alt="{{ $heroPhoto->altText() }}" fetchpriority="high"
                     @if ($heroPhoto->width) width="{{ $heroPhoto->width }}" height="{{ $heroPhoto->height }}" @endif>
            </a>
        @endif
        <div class="hero__text">
            <h1>{{ __('shop.home.title') }}</h1>
            <p class="lede">{{ __('shop.home.lede') }}</p>
            @if ($hero)
                <p class="hero__caption">
                    <a href="{{ route('pieces.show', $hero) }}">{{ $hero->reference }}, {{ $hero->localized('title') }}</a>
                    <span>{{ $hero->formattedPrice() }}</span>
                </p>
            @endif
            <a class="button" href="{{ route('pieces.index') }}">{{ __('shop.home.cta') }}</a>
        </div>
    </section>

    @if ($collections->isNotEmpty())
        <section class="collections" aria-labelledby="collections-title">
            <h2 id="collections-title" class="section-title">{{ __('shop.home.collections') }}</h2>
            <div class="collections__list">
                @foreach ($collections as $collection)
                    <a class="collection-tile" href="{{ route('collections.show', $collection) }}">
                        @if ($cover = $collection->coverUrl())
                            <img src="{{ $cover }}" alt="" loading="lazy">
                        @endif
                        <span class="collection-tile__name">{{ $collection->localized('name') }}</span>
                        @if ($intro = $collection->localized('intro'))
                            <span class="collection-tile__intro">{{ Str::limit($intro, 140) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($pieces->isNotEmpty())
        <section class="pieces-section" aria-labelledby="available-title">
            <div class="section-head">
                <h2 id="available-title" class="section-title">{{ __('shop.home.available') }}</h2>
                <a href="{{ route('pieces.index') }}">{{ __('shop.home.see_all') }}</a>
            </div>
            <div class="piece-grid">
                @foreach ($pieces as $piece)
                    <x-piece-card :piece="$piece" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="promises" aria-label="{{ __('shop.promises.label') }}">
        <div>
            <h2>{{ __('shop.promises.unique_title') }}</h2>
            <p>{{ __('shop.promises.unique_text') }}</p>
        </div>
        <div>
            <h2>{{ __('shop.promises.hallmark_title') }}</h2>
            <p>{{ __('shop.promises.hallmark_text') }}</p>
        </div>
        <div>
            <h2>{{ __('shop.promises.shipping_title') }}</h2>
            <p>{{ __('shop.promises.shipping_text') }}</p>
        </div>
    </section>
</x-layout>
