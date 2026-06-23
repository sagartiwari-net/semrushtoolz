# Semrushtoolz

Premium SEO tools group-buy platform — Laravel 13, Tailwind 4, Mail Panel, Buyahref UPI, PayPal, wallet, affiliates.

**Repository:** [github.com/sagartiwari-net/semrushtoolz](https://github.com/sagartiwari-net/semrushtoolz)

## Docs

| File | Purpose |
|------|---------|
| [BUILD_PLAN.md](BUILD_PLAN.md) | Feature phases & status |
| [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md) | Production deploy + cron checklist |
| [deploy/setup-server.sh](deploy/setup-server.sh) | One-shot server setup script |
| [BUYAHREF_SETUP.md](BUYAHREF_SETUP.md) | UPI / Buyahref webhooks |
| [PAYPAL_SETUP.md](PAYPAL_SETUP.md) | PayPal subscriptions |

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=EmailPresetSeeder
npm install && npm run dev   # terminal 1
php artisan serve            # terminal 2
```

## Production deploy (server)

```bash
git clone https://github.com/sagartiwari-net/semrushtoolz.git
cd semrushtoolz
cp deploy/production.env.template .env
# Edit .env — set APP_KEY, APP_URL, secrets
bash deploy/setup-server.sh
```

Add cron:

```cron
* * * * * cd /path/to/semrushtoolz && php artisan schedule:run >> /dev/null 2>&1
```

## Stack

- PHP 8.3+, MySQL 8+
- Laravel 13, Livewire 4, Vite, Tailwind 4, DaisyUI
- DomPDF invoices, Mail Panel transactional email

## License

Proprietary — Semrushtoolz.
