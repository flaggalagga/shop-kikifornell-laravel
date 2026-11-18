<?php

use App\Http\Controllers\Admin\OrderDocumentController;
use App\Http\Controllers\Admin\RevenueLedgerController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\CollectionController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\InquiryController;
use App\Http\Controllers\Shop\LegacyRedirectController;
use App\Http\Controllers\Shop\LocaleRedirectController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\PieceController;
use App\Http\Controllers\Shop\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::get('/', LocaleRedirectController::class)->name('root');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nDisallow: /admin\nDisallow: /*/cart\nDisallow: /*/checkout\n\nSitemap: ".route('sitemap')."\n"
)->header('Content-Type', 'text/plain'))->name('robots');

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('stripe.webhook');

// Printable documents and exports for the admin (same login as /admin).
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders/{order}/{document}', OrderDocumentController::class)
        ->whereIn('document', ['invoice', 'packing-slip'])
        ->name('orders.document');
    Route::get('/exports/livre-des-recettes', RevenueLedgerController::class)
        ->name('exports.ledger');
});

Route::prefix('{locale}')
    ->whereIn('locale', ['fr', 'en'])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', HomeController::class)->name('home');

        Route::get('/creations', [PieceController::class, 'index'])->name('pieces.index');
        Route::get('/creations/{piece}', [PieceController::class, 'show'])->name('pieces.show');
        Route::post('/creations/{piece}/question', [InquiryController::class, 'store'])
            ->middleware('throttle:5,10')
            ->name('inquiries.store');
        Route::get('/archive', [PieceController::class, 'archive'])->name('pieces.archive');

        Route::get('/collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');

        Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('/cart/destination', [CartController::class, 'zone'])->name('cart.zone');
        Route::post('/cart/{piece}', [CartController::class, 'add'])->name('cart.add');
        Route::delete('/cart/{pieceId}', [CartController::class, 'remove'])->whereNumber('pieceId')->name('cart.remove');

        Route::post('/checkout', [CheckoutController::class, 'start'])
            ->middleware('throttle:10,1')
            ->name('checkout.start');
        Route::get('/checkout/thank-you', [CheckoutController::class, 'success'])->name('checkout.success');
        Route::get('/checkout/cancelled', [CheckoutController::class, 'cancelled'])->name('checkout.cancelled');

        Route::get('/info/{page}', [PageController::class, 'show'])->name('pages.show');
    });

// Old PrestaShop URLs → new pages (301).
Route::fallback(LegacyRedirectController::class);
