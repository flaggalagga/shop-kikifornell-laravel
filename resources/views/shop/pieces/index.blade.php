<x-layout :title="__('shop.pieces.title')" :description="__('shop.pieces.intro')">
    <header class="page-head">
        <h1>{{ __('shop.pieces.title') }}</h1>
        <p class="lede">{{ __('shop.pieces.intro') }}</p>
    </header>

    @if ($pieces->isEmpty())
        <p class="empty">{{ __('shop.pieces.empty') }} <a href="{{ route('pieces.archive') }}">{{ __('shop.pieces.see_archive') }}</a></p>
    @else
        <div class="piece-grid">
            @foreach ($pieces as $piece)
                <x-piece-card :piece="$piece" />
            @endforeach
        </div>
    @endif
</x-layout>
