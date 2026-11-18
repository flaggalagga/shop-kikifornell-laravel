<?php

namespace App\Http\Controllers\Shop;

use App\Models\JewelryCollection;
use App\Models\Page;
use App\Models\Piece;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $entries = collect([
            ['home', []],
            ['pieces.index', []],
            ['pieces.archive', []],
        ]);

        JewelryCollection::query()->published()->get()
            ->each(fn ($c) => $entries->push(['collections.show', ['collection' => $c->slug]]));
        Piece::query()->visible()->get()
            ->each(fn ($p) => $entries->push(['pieces.show', ['piece' => $p->slug]]));
        Page::query()->get()
            ->each(fn ($p) => $entries->push(['pages.show', ['page' => $p->slug]]));

        $urls = $entries->map(fn ($e) => collect(config('shop.locales'))
            ->mapWithKeys(fn ($l) => [$l => route($e[0], [...$e[1], 'locale' => $l])])
            ->all());

        return response()
            ->view('shop.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
