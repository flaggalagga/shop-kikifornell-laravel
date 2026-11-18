<x-layout :title="$collection->localized('name')" :description="$collection->localized('intro')">
    <header class="page-head">
        <h1>{{ $collection->localized('name') }}</h1>
        @if ($intro = $collection->localized('intro'))
            <div class="lede prose">{!! Str::markdown($intro, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
        @endif
    </header>

    @if ($pieces->isEmpty())
        <p class="empty">{{ __('shop.collection.empty') }}</p>
    @else
        <div class="piece-grid">
            @foreach ($pieces as $piece)
                <x-piece-card :piece="$piece" />
            @endforeach
        </div>
    @endif

    @if ($soldCount > 0)
        <p class="archive-link">
            <a href="{{ route('pieces.archive') }}">{{ trans_choice('shop.collection.sold_count', $soldCount, ['count' => $soldCount]) }}</a>
        </p>
    @endif
</x-layout>
