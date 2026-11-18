<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the {locale} URL prefix (/fr/…, /en/…), applies it, and shares
 * the URL of the same page in the other language for the switcher/hreflang.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $locale = $route->parameter('locale');

        if (! in_array($locale, config('shop.locales'), true)) {
            abort(404);
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Controllers don't need the locale argument; route() fills it in.
        $route->forgetParameter('locale');

        $alternates = [];
        foreach (config('shop.locales') as $other) {
            $alternates[$other] = route($route->getName(), [...$route->parameters(), 'locale' => $other])
                .($request->getQueryString() && $other === $locale ? '?'.$request->getQueryString() : '');
        }
        View::share('alternateUrls', $alternates);

        $response = $next($request);

        if ($request->cookie('locale') !== $locale) {
            $response->headers->setCookie(cookie()->forever('locale', $locale));
        }

        return $response;
    }
}
