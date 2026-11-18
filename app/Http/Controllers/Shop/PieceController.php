<?php

namespace App\Http\Controllers\Shop;

use App\Enums\PieceStatus;
use App\Models\Piece;
use App\Support\Cart;
use Illuminate\View\View;

class PieceController
{
    /** Everything currently for sale. */
    public function index(): View
    {
        return view('shop.pieces.index', [
            'pieces' => Piece::query()->onSale()->with('photos', 'collection')
                ->orderBy('position')->orderByDesc('id')->get(),
        ]);
    }

    /** Sold pieces stay online as a record of Kiki's work. */
    public function archive(): View
    {
        return view('shop.pieces.archive', [
            'pieces' => Piece::query()->where('status', PieceStatus::Sold)->with('photos', 'collection')
                ->orderByDesc('sold_at')->get(),
        ]);
    }

    public function show(Piece $piece, Cart $cart): View
    {
        abort_unless(in_array($piece->status, PieceStatus::public(), true), 404);

        $piece->load('photos', 'collection');

        $related = Piece::query()->onSale()->with('photos')
            ->whereKeyNot($piece->id)
            ->when($piece->collection_id, fn ($q) => $q->where('collection_id', $piece->collection_id))
            ->orderBy('position')->limit(3)->get();

        return view('shop.pieces.show', [
            'piece' => $piece,
            'inCart' => $cart->has($piece),
            'related' => $related,
        ]);
    }
}
