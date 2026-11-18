<?php

namespace App\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "/" → "/fr" or "/en", from the visitor's last choice or browser language. */
class LocaleRedirectController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locales = config('shop.locales');
        $locale = $request->cookie('locale');

        if (! in_array($locale, $locales, true)) {
            $locale = $request->getPreferredLanguage($locales) ?? config('shop.default_locale');
        }

        return redirect()->route('home', ['locale' => $locale]);
    }
}
