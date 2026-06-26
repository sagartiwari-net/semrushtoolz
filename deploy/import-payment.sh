#!/usr/bin/env bash
# Import payment settings exported from local (payment:export-settings)
set -euo pipefail

SITE="${SITE:-/www/wwwroot/semrushtoolz.com}"
PHP="${PHP:-/www/server/php/84/bin/php}"
JSON="${1:-$SITE/storage/app/private/payment-settings.json}"

cd "$SITE"

if [ ! -f "$JSON" ]; then
  echo "ERROR: File not found: $JSON"
  echo "Upload payment-settings.json to storage/app/private/ first."
  exit 1
fi

$PHP deploy/import-payment-settings.php "$JSON"
$PHP artisan optimize:clear

echo ""
echo "Done. Check Admin → Payments — UPI and PayPal should show Configured."
echo "Webhook URLs (update on Buyahref + PayPal dashboards if not already):"
echo "  $(grep '^APP_URL=' .env 2>/dev/null | cut -d= -f2- | tr -d '\"')/webhooks/buyahref"
echo "  $(grep '^APP_URL=' .env 2>/dev/null | cut -d= -f2- | tr -d '\"')/webhooks/paypal"
