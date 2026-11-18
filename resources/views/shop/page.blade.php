<x-layout :title="$page->localized('title')">
    <article class="text-page">
        <h1>{{ $page->localized('title') }}</h1>
        <div class="prose">
            {!! $page->html() !!}
        </div>
    </article>
</x-layout>
