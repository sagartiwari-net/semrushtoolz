# Semrushtoolz — Server Deployment & Cron Guide

Use this checklist when hosting on production so nothing is missed.

**Repository:** https://github.com/sagartiwari-net/semrushtoolz  
**Last updated:** June 2026  
**Source of truth for schedules:** `routes/console.php`

---

## 0. Clone & first-time setup (GitHub)

On the server (PHP 8.3+, Composer, Node/npm):

```bash
cd /var/www   # or your web root
git clone https://github.com/sagartiwari-net/semrushtoolz.git
cd semrushtoolz
cp deploy/production.env.template .env
nano .env   # set APP_KEY, APP_URL, DB_PASSWORD, MAIL_PANEL_*
bash deploy/setup-server.sh
```

### MySQL (cPanel / hosting)

| Key | Value |
|-----|--------|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_DATABASE` | `semrushtoolzbd` |
| `DB_USERNAME` | `SemrushToolzBD` |
| `DB_PASSWORD` | *(from hosting panel — never commit)* |

Point the domain document root to **`public/`** (not project root).

**Do not run** `php artisan db:seed` without `--class` on production (demo users would be created).

---

## 1. One cron entry (required)

Laravel runs **all** scheduled tasks through a single scheduler. Add this to the server crontab (`crontab -e`):

```cron
* * * * * cd /path/to/SemrushToolz && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/SemrushToolz` with the real project path (e.g. `/var/www/semrushtoolz.com`).

### Important

| Item | Notes |
|------|--------|
| **Run every minute** | Required. Sub-minute jobs (e.g. `upi:verify` every 30s) only work if `schedule:run` is called every minute. |
| **PHP binary** | Use the same PHP version as the site (e.g. `/usr/bin/php8.3`). |
| **User** | Run as the web app user (`www-data`, `forge`, etc.), not root. |
| **Timezone** | Set `APP_TIMEZONE` in `.env` (e.g. `Asia/Kolkata`). Cron uses server time unless you configure otherwise. |
| **Logs (optional)** | For debugging: `>> /var/log/semrushtoolz-scheduler.log 2>&1` |

Verify after deploy:

```bash
php artisan schedule:list
php artisan schedule:run -v
```

---

## 2. All scheduled jobs (automatic via `schedule:run`)

| Command | Frequency | When / purpose |
|---------|-----------|----------------|
| `orders:expire` | Every **5 minutes** | Cancels unpaid orders past `expires_at` (`pending` / `awaiting_payment`). |
| `subscriptions:expire` | **Daily** (midnight) | Marks subscriptions as `expired` when `ends_at` has passed. |
| `subscriptions:send-expiry-reminders` | **Daily 09:00** | Plan expiry emails (7d, 3d, 2d, today, + after-expiry reminders). Requires Mail Panel + presets enabled. |
| `referral:send-signup-bonus-reminders` | **Daily 10:00** | Day 1/2/3 emails for referred users who have not purchased. Skipped if signup bonus disabled in Settings. |
| `affiliate:send-monthly-reports --batch=8` | Every **15 min** | **Only on 1st of month, 06:00–12:00.** Staggered monthly affiliate reports (8 per run). |
| `email:process-broadcast-queue --batch=8` | Every **15 min** | Sends queued affiliate promo broadcasts (8 per run). No-op when queue is empty. |
| `affiliate:release-held-commissions` | **Daily** (midnight) | Releases PayPal commissions after hold period (Settings → PayPal hold months). |
| `tools:cleanup-sessions` | Every **5 minutes** | Ends stale tool sessions past max duration. |
| `upi:verify` | Every **30 seconds** | Placeholder when Buyahref is configured (UPI handled via webhook). Safe to keep scheduled. |

### Affiliate monthly report — who gets it?

Automated send **only if** (Settings → Affiliate):

1. **Monthly report email** is enabled.
2. User has at least one commission (active affiliate).
3. **Available payout balance** > minimum (default **₹300**).
4. **Earnings in the report month** (previous calendar month on the 1st) > ₹0.
5. Not already sent for that month (`email_notification_logs`).

Admin **Send report** on Top Affiliates bypasses eligibility (force send).

### Affiliate promo broadcast — how it runs

1. Admin → **Affiliates** → **Affiliate Program Broadcast** → choose audience → **Start broadcast**.
2. Rows are inserted into `email_broadcast_queue`.
3. `email:process-broadcast-queue` drains the queue (8 emails every 15 minutes).

**Presets:** Admin → Email Presets → **Affiliate Program**

- `affiliate_program_invite` — non-affiliates (no commissions yet)
- `affiliate_program_boost` — active affiliates (current month stats)
- `affiliate_monthly_report` — automated monthly statement

After deploy, run **Admin → Email Setup → Sync All** so Mail Panel has latest templates.

---

## 3. Commands NOT in scheduler (manual / diagnostic)

| Command | Use |
|---------|-----|
| `php artisan migrate --force` | Run after each deploy. |
| `php artisan db:seed --class=EmailPresetSeeder --force` | Seed/update system email presets. |
| `php artisan paypal:status` | Check PayPal config and API connection. |
| `php artisan affiliate:send-monthly-reports --batch=1` | Manual test of monthly report batch. |
| `php artisan email:process-broadcast-queue --batch=1` | Manual test of broadcast queue. |
| `php artisan config:cache` / `route:cache` / `view:cache` | Production optimization (after `.env` is final). |

---

## 4. Queue worker (optional)

`.env` defaults to `QUEUE_CONNECTION=database`. The app currently sends most emails **synchronously** via Mail Panel in cron/commands — **no queue worker is required** for existing features.

If you later add queued jobs (`ShouldQueue`), run:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Use Supervisor to keep `queue:work` running. Ensure `jobs` table exists (`php artisan migrate`).

---

## 5. Webhooks (not cron — must be reachable)

Configure these URLs in payment provider dashboards. They must be **public HTTPS** (no auth).

| URL | Provider | Purpose |
|-----|----------|---------|
| `https://yourdomain.com/webhooks/buyahref` | Buyahref Payment Hub | UPI payment confirmation |
| `https://yourdomain.com/webhooks/paypal` | PayPal Developer | Payment capture / subscription events |

CSRF is disabled for these routes (`bootstrap/app.php`).  
See also: `BUYAHREF_SETUP.md`, `PAYPAL_SETUP.md`.

---

## 6. Production deploy checklist

### Server / app

- [ ] PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd` or `imagick`
- [ ] MySQL database (switch `.env` from SQLite — see `.env.example`)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`
- [ ] `APP_URL=https://yourdomain.com`
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link`
- [ ] `composer install` on server (includes `barryvdh/laravel-dompdf` for PDF invoices)
- [ ] HTTPS certificate (Let's Encrypt)

### Email

- [ ] Admin → **Email Setup**: Mail Panel URL + API key, enabled
- [ ] **Sync All** presets to Mail Panel
- [ ] Send test email from Email Setup

### Payments

- [ ] Admin → **Payment Integration**: Buyahref + PayPal credentials
- [ ] Webhook URLs registered with providers
- [ ] Test one UPI and one PayPal order end-to-end

### Affiliate

- [ ] Settings → Affiliate: commission %, min payout, monthly email toggle, **min payable for monthly report (₹300)**
- [ ] Cron running (`schedule:list` shows all jobs)
- [ ] Email presets synced (monthly report + invite + boost)

### Cron

- [ ] Single crontab line `* * * * * php artisan schedule:run` added
- [ ] Wait 2 minutes, check `storage/logs/laravel.log` for scheduler errors

---

## 7. Affiliate system — completion status

### Done

| Area | Status |
|------|--------|
| Referral link `?ref=CODE` + cookie capture | Done |
| Referral locked after signup | Done |
| Click tracking + 24h IP dedup | Done |
| Commission on paid orders | Done |
| Admin commission approve/reject | Done |
| PayPal commission hold + daily release cron | Done |
| Refund → commission reversal | Done |
| User dashboard: stats, commissions, payouts, reports tabs | Done |
| Reports: monthly/daily, date range, hide empty, pagination, CSV/Excel | Done |
| Monthly email: staggered 1st, 6 AM–12 PM, eligibility rules | Done |
| Promo broadcast: invite + boost presets, queued batch send | Done |
| Email preset live preview | Done |
| Signup bonus (%, days, stackable coupon, 3-day email cron) | Done |
| Admin settings for affiliate + monthly email threshold | Done |

### Affiliate — nothing critical left

Optional future improvements (not blocking launch):

- Schedule promo broadcasts on a calendar (currently **manual** from Admin → Affiliates)
- Per-affiliate email preferences (opt-out)
- More granular broadcast filters (e.g. only users with active subscription)

---

## 8. Other features — status

### Completed (recent)

| Feature | Notes |
|---------|--------|
| Terms, Privacy, Refund pages | `/terms`, `/privacy`, `/refund` — linked from footer, register, checkout |
| Admin Orders pagination | Filters (status, method, search) + 10/20/50/100 per page |
| Admin Users pagination | Search + status filter + pagination |
| PDF invoices | `barryvdh/laravel-dompdf` — download from user/admin order detail (completed/refunded) |
| BUILD_PLAN.md | Updated to reflect current state |

### Optional / future

| Feature | Notes |
|---------|--------|
| Go cloud proxy deep integration | Demo/fallback works; full webhook spec in PROJECT_SPEC |
| Scheduled affiliate promo | Manual broadcast from admin today |
| GST/tax on invoices | Not implemented |
| Optional 2FA | Not implemented |
| Admin orders CSV export | Button placeholder removed; export not built yet |

### Already in production scope

Coupons, support tickets, PayPal + Buyahref, email verification, affiliate system, security monitor — all done. See [BUILD_PLAN.md](BUILD_PLAN.md).

---

## 9. Quick test after go-live

```bash
# Scheduler
php artisan schedule:list
php artisan schedule:run -v

# Orders / subs
php artisan orders:expire
php artisan subscriptions:expire

# Affiliate (safe dry run — only sends if eligible + not already sent)
php artisan affiliate:send-monthly-reports --batch=1
php artisan affiliate:release-held-commissions

# Broadcast (only if admin queued a campaign)
php artisan email:process-broadcast-queue --batch=1

# Payments
php artisan paypal:status
php artisan upi:verify
```

---

## 10. File reference

| File | Purpose |
|------|---------|
| `routes/console.php` | All `Schedule::command(...)` definitions |
| `app/Console/Commands/*` | Individual artisan commands |
| `BUYAHREF_SETUP.md` | UPI / Buyahref webhook setup |
| `PAYPAL_SETUP.md` | PayPal + webhook setup |
| `PROJECT_SPEC.md` | Full product specification |
| `.env.example` | Production env template |

---

*When adding a new scheduled command, register it in `routes/console.php` and update this document.*
