#!/usr/bin/env bash
# Semrushtoolz — production server setup (run from project root after .env is configured)
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Composer install"
composer install --no-dev --optimize-autoloader

echo "==> NPM build"
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "WARN: npm not found — run 'npm ci && npm run build' manually or upload public/build"
fi

echo "==> App key"
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

echo "==> Migrate"
php artisan migrate --force

echo "==> Seed catalog + content (safe — no demo users)"
php artisan db:seed --class=ToolSeeder --force
php artisan db:seed --class=PlanSeeder --force
php artisan db:seed --class=ToolAccessSeeder --force
php artisan db:seed --class=ArticleSeeder --force
php artisan db:seed --class=LegalPageSeeder --force
php artisan db:seed --class=SiteSettingSeeder --force
php artisan db:seed --class=EmailPresetSeeder --force

echo "==> Storage link"
php artisan storage:link 2>/dev/null || true

echo "==> Cache"
php artisan config:cache
php artisan view:cache

echo "==> Permissions (adjust user/group for your host)"
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo ""
echo "Done. Next steps:"
echo "  1. Add cron: * * * * * cd $(pwd) && php artisan schedule:run"
echo "  2. Admin → Email Setup → Sync All"
echo "  3. Admin → Payment Integration → configure webhooks"
echo "  4. php artisan schedule:list"
