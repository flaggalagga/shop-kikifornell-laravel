<?php

/*
|--------------------------------------------------------------------------
| Kiki Fornell shop settings
|--------------------------------------------------------------------------
|
| Everything that is specific to the business lives here, read from .env
| where it differs between local and production. Values marked TODO must be
| checked before going live.
|
*/

return [

    'name' => env('SHOP_NAME', 'Kiki Fornell'),

    'tagline' => [
        'fr' => 'Bijoux en émail grand feu, faits main à Chamonix',
        'en' => 'Grand feu enamel jewellery, handmade in Chamonix',
    ],

    // Address that receives order notifications and customer enquiries.
    'contact_email' => env('SHOP_CONTACT_EMAIL', 'contact@kikifornell.art'),

    'locales' => ['fr', 'en'],
    'default_locale' => 'fr',

    'currency' => 'eur',

    /*
    | Legal identity printed on invoices and in the legal notice.
    | TODO: fill in the real values in .env before going live.
    */
    'legal' => [
        'company_name' => env('SHOP_LEGAL_NAME', 'Kiki Fornell — Ekaterina Fornell EI'),
        'address' => env('SHOP_LEGAL_ADDRESS', '[adresse], 74400 Chamonix-Mont-Blanc, France'),
        'siret' => env('SHOP_SIRET', '[SIRET]'),
        // Micro-entreprise under the VAT exemption (franchise en base).
        // Set SHOP_VAT_NUMBER and empty SHOP_VAT_NOTE if that changes.
        'vat_number' => env('SHOP_VAT_NUMBER'),
        'vat_note' => env('SHOP_VAT_NOTE', 'TVA non applicable, art. 293 B du CGI'),
    ],

    /*
    | Invoice / order numbers look like KF-2026-0001.
    */
    'order_number_prefix' => env('SHOP_ORDER_PREFIX', 'KF'),

    /*
    | How long a piece stays reserved while a customer is on the Stripe
    | checkout page. Stripe requires at least 30 minutes.
    */
    'reservation_minutes' => max(30, (int) env('SHOP_RESERVATION_MINUTES', 30)),

    /*
    | Shipping zones. The customer picks the destination in the cart, and the
    | Stripe checkout then only accepts addresses in that zone, at that price.
    | Prices are in cents. TODO: confirm prices and carriers with Kiki.
    | Shipping outside the EU needs a customs declaration (CN22/CN23).
    */
    'shipping' => [
        'france' => [
            'label' => ['fr' => 'France (et Monaco)', 'en' => 'France (and Monaco)'],
            'method' => ['fr' => 'Colissimo suivi, remise contre signature, assuré', 'en' => 'Colissimo tracked, signature on delivery, insured'],
            'price' => (int) env('SHIPPING_FRANCE_CENTS', 900),
            'days' => [2, 4],
            'countries' => ['FR', 'MC'],
        ],
        'eu' => [
            'label' => ['fr' => 'Union européenne', 'en' => 'European Union'],
            'method' => ['fr' => 'Colissimo International suivi, contre signature, assuré', 'en' => 'Colissimo International tracked, signature on delivery, insured'],
            'price' => (int) env('SHIPPING_EU_CENTS', 1900),
            'days' => [3, 8],
            'countries' => ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'],
        ],
        'international' => [
            'label' => ['fr' => 'Suisse, Royaume-Uni et international', 'en' => 'Switzerland, UK and worldwide'],
            'method' => ['fr' => 'Envoi international suivi et assuré (droits de douane à la charge du destinataire)', 'en' => 'Tracked and insured international shipping (import duties paid by the recipient)'],
            'price' => (int) env('SHIPPING_INTERNATIONAL_CENTS', 3500),
            'days' => [5, 15],
            'countries' => ['CH', 'GB', 'NO', 'IS', 'LI', 'US', 'CA', 'AU', 'NZ', 'JP', 'KR', 'SG', 'HK', 'TW', 'AE', 'IL'],
        ],
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // Requires a terms-of-service URL in Stripe Dashboard > Settings > Public details.
        'require_terms' => (bool) env('STRIPE_REQUIRE_TERMS', false),
        'dashboard_url' => env('STRIPE_DASHBOARD_URL', 'https://dashboard.stripe.com'),
    ],

    'admin' => [
        // Force every admin to set up an authenticator app on first login.
        'require_mfa' => (bool) env('ADMIN_REQUIRE_MFA', true),
    ],

    /*
    | Image sizes generated for every product photo (longest side, px).
    */
    'image_sizes' => [480, 960, 1600],

    'social' => [
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'pinterest' => env('SOCIAL_PINTEREST'),
        'facebook' => env('SOCIAL_FACEBOOK'),
    ],

];
