<?php

namespace App\Providers;

use App\Models\JewelryCollection;
use App\Models\Page;
use App\Services\Payments\CheckoutGateway;
use App\Services\Payments\StripeCheckoutGateway;
use App\Support\Cart;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CheckoutGateway::class, fn () => new StripeCheckoutGateway(
            config('shop.stripe.secret'),
            config('shop.stripe.webhook_secret'),
        ));

        $this->app->scoped(Cart::class, fn ($app) => new Cart($app['session.store']));
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Replaced per request by SetLocale; this covers error pages and emails.
        URL::defaults(['locale' => config('shop.default_locale')]);

        // Navigation, footer and cart count for every shop page.
        View::composer('components.layout', function ($view) {
            $view->with([
                'navCollections' => JewelryCollection::query()->published()->get(),
                'footerPages' => Page::query()->where('show_in_footer', true)->orderBy('position')->get(),
                'cartCount' => app(Cart::class)->count(),
            ]);
        });
    }
}
