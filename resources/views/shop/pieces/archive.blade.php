<x-layout :title="__('shop.archive.title')" :description="__('shop.archive.intro')">
    <header class="page-head">
        <h1>{{ __('shop.archive.title') }}</h1>
        <p class="lede">{{ __('shop.archive.intro') }}</p>
    </header>

    @if ($pieces->isEmpty())
        <p class="empty">{{ __('shop.archive.empty') }}</p>
    @else
        <div class="piece-grid piece-grid--archive">
            @foreach ($pieces as $piece)
                <x-piece-card :piece="$piece" />
            @endforeach
        </div>
    @endif
</x-layout>
