@php
    use App\Enums\PieceStatus;
    $status = $piece->publicStatus();
    $photos = $piece->photos;
    $title = $piece->localized('title');
    $description = $piece->localized('description');
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $title,
        'sku' => $piece->reference,
        'description' => strip_tags((string) $description),
        'image' => $photos->map(fn ($p) => $p->url(1600))->all(),
        'brand' => ['@type' => 'Brand', 'name' => config('shop.name')],
        'offers' => [
            '@type' => 'Offer',
            'price' => number_format($piece->price_cents / 100, 2, '.', ''),
            'priceCurrency' => strtoupper(config('shop.currency')),
            'availability' => $status === PieceStatus::Sold ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'url' => route('pieces.show', $piece),
        ],
    ];
@endphp
<x-layout :title="$piece->reference.' '.$title"
          :description="$piece->localized('meta_description') ?? Str::limit(strip_tags((string) $description), 160)"
          :image="$photos->first()?->url(1600)"
          :json-ld="$jsonLd">
    <div class="piece">
        <div class="gallery" data-gallery>
            @if ($photos->isNotEmpty())
                <figure class="gallery__main">
                    <img src="{{ $photos->first()->url(1600) }}" srcset="{{ $photos->first()->srcset() }}"
                         sizes="(min-width: 64rem) 58vw, 100vw" alt="{{ $photos->first()->altText() }}"
                         data-gallery-main fetchpriority="high"
                         @if ($photos->first()->width) width="{{ $photos->first()->width }}" height="{{ $photos->first()->height }}" @endif>
                </figure>
                @if ($photos->count() > 1)
                    <ul class="gallery__thumbs" aria-label="{{ __('shop.piece.photos') }}">
                        @foreach ($photos as $i => $photo)
                            <li>
                                <button type="button" data-gallery-thumb
                                        data-src="{{ $photo->url(1600) }}" data-srcset="{{ $photo->srcset() }}" data-alt="{{ $photo->altText() }}"
                                        @if ($i === 0) aria-current="true" @endif
                                        aria-label="{{ __('shop.piece.show_photo', ['n' => $i + 1]) }}">
                                    <img src="{{ $photo->url(480) }}" alt="" loading="lazy">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>

        <aside class="cartel">
            <p class="cartel__ref" aria-hidden="true">{{ $piece->reference }}</p>
            <h1 class="cartel__title">
                <span class="visually-hidden">{{ $piece->reference }}, </span>{{ $title }}
            </h1>
            @if ($piece->collection)
                <p class="cartel__collection">
                    <a href="{{ route('collections.show', $piece->collection) }}">{{ $piece->collection->localized('name') }}</a>
                </p>
            @endif

            <dl class="cartel__details">
                @if ($materials = $piece->localized('materials'))
                    <div><dt>{{ __('shop.piece.materials') }}</dt><dd>{{ $materials }}</dd></div>
                @endif
                @if ($piece->dimensions)
                    <div><dt>{{ __('shop.piece.dimensions') }}</dt><dd>{{ $piece->dimensions }}</dd></div>
                @endif
                <div><dt>{{ __('shop.piece.edition') }}</dt><dd>{{ __('shop.piece.unique') }}</dd></div>
            </dl>

            <div class="cartel__buy">
                @if ($status === PieceStatus::Sold)
                    <p class="cartel__price"><span class="status status--sold">{{ __('shop.status.sold') }}</span></p>
                    <p class="cartel__note">{{ __('shop.piece.sold_note') }}</p>
                    <a class="button button--quiet" href="#question">{{ __('shop.piece.ask_commission') }}</a>
                @elseif ($status === PieceStatus::Reserved)
                    <p class="cartel__price">{{ $piece->formattedPrice() }}</p>
                    <p class="cartel__note">{{ __('shop.piece.reserved_note') }}</p>
                @else
                    <p class="cartel__price">{{ $piece->formattedPrice() }}</p>
                    @if ($inCart)
                        <a class="button" href="{{ route('cart.show') }}">{{ __('shop.piece.in_cart') }}</a>
                    @else
                        <form method="post" action="{{ route('cart.add', $piece) }}">
                            @csrf
                            <button class="button" type="submit">{{ __('shop.piece.add_to_cart') }}</button>
                        </form>
                    @endif
                    <p class="cartel__note">{{ __('shop.piece.shipping_note') }}</p>
                @endif
            </div>

            @if ($description)
                <div class="prose cartel__description">
                    {!! Str::markdown($description, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                </div>
            @endif
        </aside>
    </div>

    <section class="question" id="question" aria-labelledby="question-title">
        <h2 id="question-title">{{ __('shop.inquiry.title') }}</h2>
        <p>{{ __('shop.inquiry.intro') }}</p>
        <form method="post" action="{{ route('inquiries.store', $piece) }}" class="form">
            @csrf
            <div class="form__row">
                <label for="inq-name">{{ __('shop.inquiry.name') }}</label>
                <input id="inq-name" name="name" type="text" autocomplete="name" required maxlength="120" value="{{ old('name') }}">
                @error('name')<p class="form__error">{{ $message }}</p>@enderror
            </div>
            <div class="form__row">
                <label for="inq-email">{{ __('shop.inquiry.email') }}</label>
                <input id="inq-email" name="email" type="email" autocomplete="email" required maxlength="190" value="{{ old('email') }}">
                @error('email')<p class="form__error">{{ $message }}</p>@enderror
            </div>
            <div class="form__row form__row--wide">
                <label for="inq-message">{{ __('shop.inquiry.message') }}</label>
                <textarea id="inq-message" name="message" rows="5" required maxlength="4000">{{ old('message') }}</textarea>
                @error('message')<p class="form__error">{{ $message }}</p>@enderror
            </div>
            <div class="hp" aria-hidden="true">
                <label for="inq-website">Website</label>
                <input id="inq-website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <button class="button button--quiet" type="submit">{{ __('shop.inquiry.send') }}</button>
        </form>
    </section>

    @if ($related->isNotEmpty())
        <section class="pieces-section" aria-labelledby="related-title">
            <h2 id="related-title" class="section-title">{{ __('shop.piece.related') }}</h2>
            <div class="piece-grid">
                @foreach ($related as $relatedPiece)
                    <x-piece-card :piece="$relatedPiece" />
                @endforeach
            </div>
        </section>
    @endif
</x-layout>
