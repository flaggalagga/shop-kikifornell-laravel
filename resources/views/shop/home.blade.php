<x-layout>
    @php $heroPhoto = $hero?->mainPhoto(); @endphp
    <section class="hero @unless ($heroPhoto) hero--plain @endunless">
        @if ($heroPhoto)
            <a class="hero__image" href="{{ route('pieces.show', $hero) }}" tabindex="-1" aria-hidden="true">
                <img src="{{ $heroPhoto->url(1600) }}" srcset="{{ $heroPhoto->srcset() }}" sizes="100vw"
                     alt="" fetchpriority="high"
                     @if ($heroPhoto->width) width="{{ $heroPhoto->width }}" height="{{ $heroPhoto->height }}" @endif>
            </a>
        @endif
        <div class="hero__text">
            <h1 class="hero__title">{{ __('shop.home.title') }}</h1>
            <p class="hero__place">Chamonix-Mont-Blanc</p>
            <a class="button button--outline" href="{{ route('pieces.index') }}">{{ __('shop.home.cta') }}</a>
        </div>
    </section>

    @if ($collections->isNotEmpty())
        <section class="feature-rows" aria-label="{{ __('shop.home.collections') }}">
            @foreach ($collections as $collection)
                <article class="feature-row">
                    <a class="feature-row__image" href="{{ route('collections.show', $collection) }}" tabindex="-1" aria-hidden="true">
                        @if ($cover = $collection->coverUrl())
                            <img src="{{ $cover }}" alt="" loading="lazy">
                        @endif
                    </a>
                    <div class="feature-row__text">
                        <h2><a href="{{ route('collections.show', $collection) }}">{{ $collection->localized('name') }}</a></h2>
                        @if ($intro = $collection->localized('intro'))
                            <div class="prose">{!! Str::markdown($intro, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                        @endif
                        <p class="feature-row__cta">
                            <a class="button button--outline" href="{{ route('collections.show', $collection) }}">{{ __('shop.home.see_collection') }}</a>
                        </p>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    @if ($pieces->isNotEmpty())
        <section class="pieces-section" aria-labelledby="available-title">
            <div class="section-head">
                <h2 id="available-title">{{ __('shop.home.available') }}</h2>
                <p><a href="{{ route('pieces.index') }}">{{ __('shop.home.see_all') }}</a></p>
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
