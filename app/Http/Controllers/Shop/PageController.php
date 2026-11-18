<?php

namespace App\Http\Controllers\Shop;

use App\Models\Page;
use Illuminate\View\View;

class PageController
{
    public function show(Page $page): View
    {
        return view('shop.page', ['page' => $page]);
    }
}
