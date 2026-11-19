#!/usr/bin/env bash
#
# One-time setup of the shop on the OVH hosting. Run it over SSH from the
# app folder, after the code has been uploaded (by the GitHub deploy):
#
#   cd ~/shop-laravel && bash deploy/first-install.sh https://new.kikifornell.art
#
# 1st run: creates .env with production defaults and an app key, then stops
#          so you can fill in the database, email and Stripe settings.
# 2nd run: checks the database connection, creates the tables and the
#          standard pages, links the photo folder, creates admin logins.
#
# Safe to run again later: it never overwrites an existing .env or data.

set -euo pipefail

PHP="${PHP_BIN:-php}"
APP_URL="${1:-}"

cd "$(dirname "$0")/.."

if [ ! -f artisan ] || [ ! -d vendor ]; then
    echo "Run this from the uploaded app folder (artisan and vendor/ are missing)."
    exit 1
fi

echo "PHP: $($PHP -r 'echo PHP_VERSION;')"

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache

# ---- 1st run: create .env --------------------------------------------------
if [ ! -f .env ]; then
    if [ -z "$APP_URL" ]; then
        echo "Give the site address, e.g.: bash deploy/first-install.sh https://new.kikifornell.art"
        exit 1
    fi

    cp .env.example .env
    sed -i \
        -e 's#^APP_ENV=.*#APP_ENV=production#' \
        -e 's#^APP_DEBUG=.*#APP_DEBUG=false#' \
        -e "s#^APP_URL=.*#APP_URL=${APP_URL}#" \
        -e 's#^LOG_LEVEL=.*#LOG_LEVEL=warning#' \
        -e 's#^DB_CONNECTION=.*#DB_CONNECTION=mysql#' \
        -e 's#^\# DB_HOST=#DB_HOST=#' \
        -e 's#^\# DB_PORT=#DB_PORT=#' \
        -e 's#^\# DB_DATABASE=#DB_DATABASE=#' \
        -e 's#^\# DB_USERNAME=#DB_USERNAME=#' \
        -e 's#^\# DB_PASSWORD=#DB_PASSWORD=#' \
        -e 's#^SESSION_SECURE_COOKIE=.*#SESSION_SECURE_COOKIE=true#' \
        -e 's#^MAIL_MAILER=.*#MAIL_MAILER=smtp#' \
        -e 's#^MAIL_SCHEME=.*#MAIL_SCHEME=smtps#' \
        .env
    chmod 600 .env
    $PHP artisan key:generate --force

    cat <<'EOF'

.env created with production defaults and a new app key.

Now edit it (nano .env) and fill in:
  DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD   (OVH panel > Bases de données)
  MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS  (an OVH mailbox)
  SHOP_CONTACT_EMAIL, SHOP_LEGAL_*, SHOP_SIRET
  STRIPE_SECRET, STRIPE_WEBHOOK_SECRET             (test keys for now)

Then run this script again.
EOF
    exit 0
fi

# ---- 2nd run: database, pages, admin --------------------------------------
# (No `db:show` check: it runs server-status queries that shared MySQL may refuse.)
if ! $PHP artisan migrate --force; then
    echo
    echo "Creating the tables failed (message above). Check DB_* in .env, then run this script again."
    exit 1
fi
$PHP artisan db:seed --class="Database\Seeders\PageSeeder" --force
[ -e public/storage ] || $PHP artisan storage:link

if [ "$($PHP artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -1)" = "0" ]; then
    echo
    echo "Create the first admin login (repeat later with: php artisan make:filament-user):"
    $PHP artisan make:filament-user
fi

$PHP artisan optimize
$PHP artisan filament:optimize

cat <<'EOF'

Done. Next:
  - OVH panel > Tâches planifiées - Cron: shop-laravel/ovh-cron.php, PHP 8.5, every hour
  - Stripe > Webhooks: <site>/stripe/webhook (events listed in README.md)
  - Log in at <site>/admin and set up the authenticator app
From now on, every push to main deploys automatically.
EOF
