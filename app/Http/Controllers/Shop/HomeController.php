<?php

namespace App\Http\Controllers\Shop;

use App\Models\JewelryCollection;
use App\Models\Piece;
use Illuminate\View\View;

class HomeController
{
    public function __invoke(): View
    {
        $onSale = Piece::query()->onSale()->with('photos', 'collection')
            ->orderByDesc('is_featured')->orderBy('position')->orderByDesc('id')
            ->get();

        return view('shop.home', [
            'hero' => $onSale->firstWhere('is_featured', true) ?? $onSale->first(),
            'pieces' => $onSale->take(9),
            'collections' => JewelryCollection::query()->published()->get(),
        ]);
    }
}
