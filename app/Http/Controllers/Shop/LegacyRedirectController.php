<?php

namespace App\Http\Controllers\Shop;

use App\Models\JewelryCollection;
use App\Models\Piece;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Catches old PrestaShop URLs and sends them (301) to the new pages, so
 * search engines and old links keep working. Pieces and collections keep
 * their PrestaShop id in `legacy_id` (set by `php artisan shop:import-prestashop`).
 *
 *   /en/colliers/12-p002-necklace.html   → piece with legacy_id 12
 *   /fr/7-les-pendeloques                → collection with legacy_id 7
 *   /index.php?id_product=12&controller=product
 */
class LegacyRedirectController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $path = '/'.ltrim($request->path(), '/');
        $locale = preg_match('#^/(fr|en)(/|$)#', $path, $m) ? $m[1] : config('shop.default_locale');

        $productId = $request->query('id_product')
            ?? (preg_match('#/(\d+)(?:-\d+)?-[^/]*\.html$#', $path, $m) ? $m[1] : null);

        if ($productId) {
            $piece = Piece::query()->visible()->where('legacy_id', (int) $productId)->first();

            return $piece
                ? redirect()->route('pieces.show', ['locale' => $locale, 'piece' => $piece->slug], 301)
                : redirect()->route('pieces.index', ['locale' => $locale], 301);
        }

        $categoryId = $request->query('id_category')
            ?? (preg_match('#^/(?:fr|en)/(\d+)-[a-z0-9-]+$#', $path, $m) ? $m[1] : null);

        if ($categoryId) {
            $collection = JewelryCollection::query()->where('legacy_id', (int) $categoryId)->first();

            return $collection
                ? redirect()->route('collections.show', ['locale' => $locale, 'collection' => $collection->slug], 301)
                : redirect()->route('pieces.index', ['locale' => $locale], 301);
        }

        abort(404);
    }
}
