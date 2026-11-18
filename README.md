# Kiki Fornell — shop

Online shop for Kiki Fornell's handmade grand feu enamel jewellery: a bilingual (FR/EN) storefront, an admin for Kiki, and payments through Stripe's hosted checkout. Built to run on the existing **OVH web hosting (Pro plan)**: PHP + MySQL, no Node.js, no background workers.

- **Storefront** — `/fr/…` and `/en/…`: home, all pieces, collections, piece pages with gallery, sold-pieces archive, cart, legal pages, "ask about this piece" form, sitemap, product data for Google, 301 redirects from the old PrestaShop URLs.
- **Admin** — `/admin` (Filament, in French): pieces with photos, collections, editable pages, orders (mark shipped → email with tracking, invoice and packing slip to print as PDF), customer messages, income-ledger CSV export, two-factor login.
- **Payments** — Stripe Checkout. Card data never touches this app.

## How it works

Every piece is **one of a kind**, so there is no stock count. A piece moves through:

```
draft ──► available ──► reserved ──► sold
              ▲            │  (customer on the Stripe page, 30 min)
              └────────────┘  payment abandoned / expired / cancelled
archived = hidden from the site
```

Checkout flow:

1. The customer picks a destination zone in the cart (France / EU / international). Price and allowed countries come from `config/shop.php`.
2. **Pay** → `StartCheckout` locks the pieces' rows, marks them `reserved`, and opens a Stripe Checkout Session that expires after 30 minutes (Stripe's minimum). Two customers can never reserve the same piece.
3. Stripe calls `POST /stripe/webhook`:
   - `checkout.session.completed` (paid) → `FulfillCheckout` creates the order (number `KF-2026-0001`, also the invoice number), marks pieces `sold`, emails the customer and the shop. Idempotent.
   - `checkout.session.expired` / `async_payment_failed` → pieces back on sale.
   - `checkout.session.completed` (unpaid, slow payment methods) → pieces held until `async_payment_succeeded`.
   - `charge.refunded` → order marked refunded and flagged for Kiki.
4. If a payment ever arrives for a piece that was no longer held, the order is created anyway and **flagged "À vérifier"** in the admin.

Design choices worth knowing:

| Choice | Why |
|---|---|
| FR/EN as plain columns (`title_fr`, `title_en`) | Two fixed languages; easy to query, export and review. `HasLocalizedFields::localized()` picks the right one with fallback. |
| No CSS/JS build step | `public/css/shop.css` and `public/js/shop.js` are hand-written; nothing to compile on OVH. Fonts are self-hosted (no Google Fonts → no GDPR issue). |
| Photos resized on upload | `ImageVariants` makes 480/960/1600 px WebP copies and strips EXIF (phones store GPS there). |
| Everything synchronous | OVH shared hosting has no queue workers and runs cron at most hourly. |
| Stripe behind `CheckoutGateway` | Tests use a fake gateway; webhook signatures are checked for real. |

Code map:

```
app/Actions/            StartCheckout, FulfillCheckout, ReleaseCheckout   ← the money logic
app/Services/Payments/  CheckoutGateway + Stripe implementation
app/Http/Controllers/   Shop/* (storefront), StripeWebhookController, Admin/* (invoice, CSV)
app/Filament/           admin resources (Pieces, Collections, Pages, Orders, Inquiries)
app/Console/Commands/   shop:import-prestashop, shop:release-expired-reservations
config/shop.php         business settings: legal identity, shipping zones, Stripe
resources/views/shop/   storefront templates          lang/{fr,en}/shop.php  texts
public/css/shop.css     storefront styles (design notes at the top)
```

## Local development

Requirements: PHP 8.3+ with `gd`, `intl`, `pdo_sqlite`; Composer.

```bash
composer setup                 # install, .env, key, SQLite database, pages, storage link
php artisan db:seed --class=DemoSeeder   # demo pieces with placeholder photos
php artisan make:filament-user           # admin login
composer dev                   # http://127.0.0.1:8000  — admin at /admin
```

Set `ADMIN_REQUIRE_MFA=false` in `.env` locally if you don't want to set up an authenticator app. Emails go to `storage/logs/laravel.log`.

Stripe locally: put test keys in `.env`, then forward webhooks with the [Stripe CLI](https://docs.stripe.com/stripe-cli):

```bash
stripe listen --forward-to 127.0.0.1:8000/stripe/webhook   # prints the whsec_… to put in STRIPE_WEBHOOK_SECRET
```

Pay with card `4242 4242 4242 4242`, any future date, any CVC.

Tests and code style:

```bash
php artisan test
vendor/bin/pint
```

## Install on OVH

> **Check this first.** OVH applies **one PHP version to the whole hosting** (`.ovhconfig` at the FTP root). Laravel 13 needs **PHP 8.3+**. The old PrestaShop runs on the same hosting: PrestaShop 8.x only supports PHP up to 8.1, PrestaShop 9 supports 8.1–8.4. If the shop is on PrestaShop 8, export everything from it first (see *Moving from PrestaShop*), then switch the PHP version at cut-over, or put the new shop on a second hosting for the transition.

1. **Database** — OVH Control Panel > Web Cloud > Hébergements > *your hosting* > Bases de données > Créer. Note host, name, user, password.
2. **PHP version** — copy `deploy/ovhconfig.example` to `.ovhconfig` at the FTP root (after the check above).
3. **SSH** — enable SSH for the FTP user (Pro plan). For automatic deploys, add the deploy public key to `~/.ssh/authorized_keys` on the hosting.
4. **Upload the code** to a folder, e.g. `~/shop`:
   - *automatic*: set up the GitHub Actions secrets/variables listed at the top of `.github/workflows/deploy.yml`, set `DEPLOY_ENABLED=true`, and push to `main`;
   - *by hand*: run `composer install --no-dev --optimize-autoloader` locally and upload everything except `.env`, `storage/`, `tests/`.
5. **Domain** — Multisite > modify `shop.kikifornell.art` (or a test subdomain first, e.g. `new.kikifornell.art`) and set its **root folder to `shop/public`**. Enable SSL (Let's Encrypt) for it.
6. **`.env` on the server** — over SSH in `~/shop`: `cp .env.example .env`, then set at least:
   `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://shop.kikifornell.art`, `LOG_LEVEL=warning`, `DB_CONNECTION=mysql` + `DB_*`, `SESSION_SECURE_COOKIE=true`, `MAIL_MAILER=smtp` + OVH mailbox, `SHOP_*` legal identity, `STRIPE_*`.
7. **First run** (over SSH; if `php -v` shows the wrong version, use `/usr/local/php8.4/bin/php`):
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --class=PageSeeder --force
   php artisan storage:link
   php artisan make:filament-user        # Kiki's and your logins
   php artisan optimize && php artisan filament:optimize
   ```
8. **Scheduled task** — Tâches planifiées - Cron > Ajouter: command `shop/ovh-cron.php`, PHP 8.4, every hour. (OVH runs a PHP file, at most hourly; it runs Laravel's scheduler.)
9. **Upload size** — photos up to 15 MB are accepted by the admin; if uploads fail, check `upload_max_filesize` / `post_max_size` in the OVH panel.

## Stripe setup

1. Dashboard > Developers > API keys → `STRIPE_SECRET` (start with `sk_test_…`).
2. Dashboard > Developers > Webhooks > Add endpoint: `https://shop.kikifornell.art/stripe/webhook`, events:
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `checkout.session.expired`, `charge.refunded`.
   Copy its signing secret → `STRIPE_WEBHOOK_SECRET`.
3. Settings > Payment methods: cards, Apple Pay, Google Pay (Klarna for paying in instalments is worth considering for the expensive pieces).
4. Settings > Branding: logo and colour (`#2b675f`) so the checkout page matches the shop.
5. Settings > Public details: add the terms-of-sale URL (`/fr/info/cgv`), then set `STRIPE_REQUIRE_TERMS=true` so customers must tick it.
6. At launch: switch to live keys and create the live webhook endpoint (it has its own secret).

## Moving from PrestaShop

The PrestaShop database is on the same OVH hosting.

```bash
# 1. Copy PrestaShop's product photos (folder img/p) next to the new app, e.g. ~/ps-img-p
# 2. In .env: PRESTASHOP_DB_HOST / _DATABASE / _USERNAME / _PASSWORD (from PrestaShop's app/config/parameters.php)
php artisan shop:import-prestashop --images=/home/<user>/ps-img-p --dry-run   # check the table it prints
php artisan shop:import-prestashop --images=/home/<user>/ps-img-p
```

- Top-level categories become collections; sub-categories (Colliers, Earrings…) are folded into their collection.
- Inactive products → draft; out of stock → sold (they appear in the archive); otherwise for sale.
- PrestaShop stores prices **without tax**. If the old shop added VAT, use `--tax-rate=20`. The command warns if it finds tax rules.
- Safe to re-run; photos are only imported for pieces that have none.
- PrestaShop ids are kept, so old URLs (`/en/colliers/12-p002-necklace.html`, `/fr/7-les-pendeloques`) redirect (301) to the new pages.

Before switching the domain, keep a full export of the PrestaShop orders and invoices: French accounting rules require keeping them for 10 years.

## Before launch

- [ ] Legal pages (Admin > Pages): replace every `[…]` placeholder; add the consumer mediator; have them checked.
- [ ] `.env`: `SHOP_LEGAL_*`, `SHOP_SIRET`, contact email; confirm the VAT note still applies.
- [ ] Shipping prices and carriers per zone (`SHIPPING_*` in `.env`, labels in `config/shop.php`).
- [ ] Every piece: FR and EN text, photo descriptions, dimensions.
- [ ] Stripe live keys + live webhook; one real order end-to-end, then refund it.
- [ ] Both admin accounts set up with an authenticator app.
- [ ] Test emails arrive (and not in spam): order confirmation, shipping notice, enquiry.

## Day-to-day (for Kiki)

- **New piece**: Admin > Pièces > Créer. Add photos (the first is the main one), FR and EN texts, price; set status to *En vente*.
- **New order**: you get an email. Admin > Commandes: print the *Bon de livraison*, pack, then *Marquer comme expédiée* with the tracking number: the customer gets an email.
- **Refund**: in Stripe (*Voir dans Stripe*). The order is then marked refunded; put the piece back on sale if it came back.
- **Accounting**: Admin > Commandes > *Livre des recettes (CSV)*; *Facture* on each order.
- **Sold at a market**: set the piece's status to *Vendu*.

## Working on this with AI

Conventions for AI coding assistants are in [`CLAUDE.md`](CLAUDE.md) (also read via `AGENTS.md`). Changes go through pull requests: tests and code style run on every push, and deploys only happen from `main`.
