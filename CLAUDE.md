# Working on the Kiki Fornell shop

Read `README.md` first: it explains the piece lifecycle, the checkout flow and the code map.

## Stack and constraints

- Laravel 13, Filament 5 (admin), Stripe Checkout (hosted), plain Blade + hand-written CSS/JS for the storefront.
- Production is **OVH shared web hosting**: PHP + MySQL only. No Node.js at runtime, no queue workers (`QUEUE_CONNECTION=sync`), scheduled tasks at most **hourly** (`ovh-cron.php`). Don't add anything that needs a daemon, Redis, websockets or a build step on the server.
- Local and tests use SQLite; production uses MySQL. Write migrations that work on both.

## Rules

1. **Money code is off-limits without tests.** `app/Actions/*Checkout*`, `StripeWebhookController`, `Order::nextNumber()` and the piece status logic. Any change there needs a test in `tests/Feature/CheckoutTest.php` or `StripeWebhookTest.php`, and must stay idempotent (Stripe resends events).
2. **Never handle card data.** Payments stay on Stripe's hosted page. Refunds and disputes are done in the Stripe Dashboard, not in code.
3. **Ask before changing**: prices, shipping rates, legal texts (CGV, mentions légales, privacy), the VAT note, anything that switches Stripe to live mode.
4. **Both languages, always.** Every customer-facing string goes in `lang/fr/shop.php` *and* `lang/en/shop.php`. Content fields come in pairs (`title_fr` / `title_en`); use `$model->localized('title')` in views.
5. **Admin UI is in French** (Kiki uses it): labels, notifications, help texts.
6. **Secrets** live in `.env` and GitHub secrets only. Never commit `.env`, keys, or customer data.
7. Before committing: `php artisan test` and `vendor/bin/pint` must pass.

## Conventions

- Pieces are unique: there is no stock or quantity anywhere. Status is `App\Enums\PieceStatus`.
- Public URLs use slugs (`/fr/creations/m015-lac-vert-earrings`); admin URLs use ids.
- Business settings belong in `config/shop.php` (read from `.env` when they differ per environment).
- Prices are integers in cents (`price_cents`); format with `App\Support\Money::format()`.
- Customer emails are sent in the order's locale: `Mail::to(...)->locale($order->locale)`.
- Mail failures are logged, never thrown from the webhook (Stripe would retry and the order already exists).

## Storefront design

Direction "fire in ice": cool glacier palette, enamel colours come from the photos. Tokens are at the top of `public/css/shop.css`:
Névé `#f3f6f6` (page), Glace `#dfe7ea` (panels), Encre `#1b2a3b` (text), Brume `#59677a` (secondary), Lac Vert `#2b675f` (actions), Or `#a47d2c` (thin rules only).
Type: Gloock (display), Hanken Grotesk (text), both self-hosted. The one bold element is the large reference number on piece pages; keep everything else quiet. No all-caps labels, no scroll animations, sentence case everywhere. Check mobile (390 px) and keyboard focus for any visual change.

## Writing copy

Plain, warm, specific; written for the customer, in Kiki's voice. Kiki reviews every product description. Don't invent facts about materials, techniques or legal guarantees.
