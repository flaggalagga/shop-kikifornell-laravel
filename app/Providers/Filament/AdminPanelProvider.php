<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ShopStats;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName(config('shop.name'))],
                isRequired: config('shop.admin.require_mfa'),
            )
            ->brandName(config('shop.name'))
            ->favicon('/favicon.svg')
            ->colors([
                // "Lac Vert", the shop's action colour (600 = #2b675f).
                'primary' => [
                    50 => '#eef6f4', 100 => '#d5ebe6', 200 => '#acd6cd', 300 => '#7cbbad',
                    400 => '#4f9a8c', 500 => '#357f72', 600 => '#2b675f', 700 => '#23534d',
                    800 => '#1e4440', 900 => '#1a3936', 950 => '#0e2220',
                ],
                'gray' => Color::Slate,
            ])
            ->navigationGroups(['Ventes', 'Boutique', 'Site'])
            ->navigationItems([
                NavigationItem::make('Voir la boutique')
                    ->url(fn () => route('home', ['locale' => 'fr']), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->group('Site')
                    ->sort(10),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                ShopStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
