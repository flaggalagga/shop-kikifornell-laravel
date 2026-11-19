@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'jsonLd' => null,
])
@php
    $locale = app()->getLocale();
    $alternateUrls = $alternateUrls ?? [];
    $otherLocale = collect(config('shop.locales'))->first(fn ($l) => $l !== $locale);
    $pageTitle = $title ? $title.' — '.config('shop.name') : config('shop.name').' — '.config('shop.tagline.'.$locale);
    $metaDescription = $description ?? config('shop.tagline.'.$locale);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.replace('no-js', 'js')</script>
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($metaDescription), 160) }}">
    <link rel="canonical" href="{{ $alternateUrls[$locale] ?? url()->current() }}">
    @foreach ($alternateUrls as $l => $url)
        <link rel="alternate" hreflang="{{ $l }}" href="{{ $url }}">
    @endforeach
    <meta property="og:title" content="{{ $title ?? config('shop.name') }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($metaDescription), 200) }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ $locale === 'fr' ? 'fr_FR' : 'en_GB' }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    <link rel="preload" href="{{ asset('fonts/figtree-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/alegreya-sans-latin-700-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ filemtime(public_path('css/shop.css')) }}">
    @if ($adobeKit = config('shop.fonts.adobe_kit'))
        {{-- Kiki's own Adobe Fonts (same as kikifornell.art); the self-hosted look-alikes stay as fallback. --}}
        <link rel="stylesheet" href="https://use.typekit.net/{{ $adobeKit }}.css">
        <style>
            :root {
                --display: "{{ config('shop.fonts.display') }}", "Alegreya Sans", "Gill Sans", sans-serif;
                --text-font: "{{ config('shop.fonts.text') }}", "Figtree", "Helvetica Neue", Arial, sans-serif;
            }
        </style>
    @endif
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @if ($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
</head>
<body>
    <a class="skip-link" href="#main">{{ __('shop.nav.skip') }}</a>

    <header class="site-header" id="top">
        <a class="wordmark" href="{{ route('home') }}">{{ config('shop.name') }}</a>

        <button type="button" class="menu-toggle" aria-controls="main-nav" aria-expanded="false" hidden>{{ __('shop.nav.menu') }}</button>
        <nav class="main-nav" id="main-nav" aria-label="{{ __('shop.nav.main') }}">
            <ul>
                @foreach ($navCollections as $navCollection)
                    <li><a href="{{ route('collections.show', $navCollection) }}" @if (request()->route('collection')?->is($navCollection)) aria-current="page" @endif>{{ $navCollection->localized('name') }}</a></li>
                @endforeach
                <li><a href="{{ route('pieces.index') }}" @if (request()->routeIs('pieces.index')) aria-current="page" @endif>{{ __('shop.nav.pieces') }}</a></li>
                <li><a href="{{ route('pages.show', 'atelier') }}" @if (request()->route('page')?->slug === 'atelier') aria-current="page" @endif>{{ __('shop.nav.atelier') }}</a></li>
                <li><a href="mailto:{{ config('shop.contact_email') }}">{{ __('shop.nav.contact') }}</a></li>
            </ul>
        </nav>

        <div class="header-tools">
            @if ($otherLocale && isset($alternateUrls[$otherLocale]))
                <a href="{{ $alternateUrls[$otherLocale] }}" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}" aria-label="{{ __('shop.nav.switch_to', [], $otherLocale) }}"><span class="lang-long">{{ __('shop.nav.switch_to', [], $otherLocale) }}</span><span class="lang-short" aria-hidden="true">{{ strtoupper($otherLocale) }}</span></a>
            @endif
            <a href="{{ route('cart.show') }}" class="cart-link">
                {{ __('shop.nav.cart') }}@if ($cartCount > 0)<span class="cart-count" aria-label="{{ trans_choice('shop.nav.cart_items', $cartCount) }}">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </header>

    @if (session('notice'))
        <p class="notice" role="status">{{ session('notice') }}</p>
    @endif

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <a class="back-to-top" href="#top">↑ {{ __('shop.nav.back_to_top') }}</a>
        <p><a class="wordmark" href="{{ route('home') }}">{{ config('shop.name') }}</a></p>
        <nav aria-label="{{ __('shop.nav.footer') }}">
            <ul>
                <li><a href="{{ route('pieces.archive') }}">{{ __('shop.nav.archive') }}</a></li>
                @foreach ($footerPages as $footerPage)
                    <li><a href="{{ route('pages.show', $footerPage) }}">{{ $footerPage->localized('title') }}</a></li>
                @endforeach
            </ul>
            <ul>
                <li><a href="mailto:{{ config('shop.contact_email') }}">{{ config('shop.contact_email') }}</a></li>
                @foreach (array_filter(config('shop.social')) as $network => $url)
                    <li><a href="{{ $url }}" rel="me noopener" target="_blank">{{ ucfirst($network) }}</a></li>
                @endforeach
            </ul>
        </nav>
        <p class="footer-legal">© {{ config('shop.name') }}, {{ now()->year }}</p>
    </footer>

    <script src="{{ asset('js/shop.js') }}?v={{ filemtime(public_path('js/shop.js')) }}" defer></script>
</body>
</html>
