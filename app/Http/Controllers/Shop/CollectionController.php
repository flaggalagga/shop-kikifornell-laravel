<?php

namespace App\Http\Controllers\Shop;

use App\Enums\PieceStatus;
use App\Models\JewelryCollection;
use Illuminate\View\View;

class CollectionController
{
    public function show(JewelryCollection $collection): View
    {
        abort_unless($collection->is_published, 404);

        return view('shop.collections.show', [
            'collection' => $collection,
            'pieces' => $collection->publicPieces()->with('photos')->get(),
            'soldCount' => $collection->pieces()->where('status', PieceStatus::Sold)->count(),
        ]);
    }
}
