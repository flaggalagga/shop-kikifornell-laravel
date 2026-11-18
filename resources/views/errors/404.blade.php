<x-layout :title="__('shop.errors.not_found_title')">
    <article class="text-page">
        <h1>{{ __('shop.errors.not_found_title') }}</h1>
        <p class="lede">{{ __('shop.errors.not_found_text') }}</p>
        <a class="button" href="{{ route('pieces.index') }}">{{ __('shop.home.cta') }}</a>
    </article>
</x-layout>
