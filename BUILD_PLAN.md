# Semrushtoolz.com — Build Plan

> Laravel 13 + Livewire 4 + Tailwind 4 + DaisyUI + Mail Panel + Buyahref/PayPal

**Last updated:** June 2026  
**Deploy guide:** see [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md)

## Design System

| Layer | Tool | Purpose |
|-------|------|---------|
| **Theme tokens** | CSS variables (`--accent`, etc.) | Orange/gray palette |
| **Components** | DaisyUI + custom `.ui-*` classes | Buttons, cards, forms |
| **Icons** | Lucide (SVG) | Line icons |
| **Font** | Inter | Dashboard + public site |

---

## Phase 1 — Foundation ✅ Done

- [x] Laravel 13 project setup
- [x] Livewire 4, Tailwind 4, DaisyUI
- [x] Public layout, homepage, pricing
- [x] Dashboard + admin layouts
- [x] MySQL-ready `.env.example`
- [ ] Production MySQL cutover on live server (per deploy)

## Phase 2 — Auth & Users ✅ Done

- [x] Register, login, logout
- [x] Email verification
- [x] OTP login (periodic + email codes)
- [x] Password reset via Mail Panel
- [x] User model: referral, status, preferences
- [x] Admin auth + middleware
- [x] Security monitor (multi-IP alerts)
- [ ] Optional 2FA (not planned yet)

## Phase 3 — Plans & Shop ✅ Done

- [x] Plans, tools, subscriptions, orders
- [x] Shop → checkout → payment flows
- [x] Coupon codes at checkout
- [x] Referral signup bonus (%, days, email reminders)
- [x] Admin order approve / reject / refund

## Phase 4 — Payments ✅ Done

- [x] Buyahref UPI + webhook
- [x] PayPal subscriptions + webhook
- [x] Offline payment + proof upload
- [x] Order expiry cron
- [x] Admin payments dashboard (UPI / Offline tabs)
- [x] **PDF invoices** (completed + refunded orders)

## Phase 5 — Tool Access ✅ Done

- [x] Go proxy client + demo fallback
- [x] Tool sessions + cleanup cron
- [x] Chrome extension settings + API

## Phase 6 — Admin Panel ✅ Done

- [x] Dashboard stats, orders, users, payments
- [x] Plans, tools, tool groups, servers CRUD
- [x] Coupons CRUD
- [x] Articles CMS
- [x] Settings (general, affiliate, support FAQs)
- [x] Email presets + Mail Panel sync + **live preview**
- [x] **Admin orders/users pagination** (10/20/50/100)

## Phase 7 — Affiliate Program ✅ Done

- [x] Referral capture (`?ref=`), cookie, locked referrer
- [x] Click tracking + dedup
- [x] Commissions, payouts, PayPal hold + release cron
- [x] Refund → commission reversal
- [x] User dashboard: commissions, payouts, reports (monthly/daily, filters, export)
- [x] Staggered monthly report emails (eligibility: payable > ₹300 + report-month earnings)
- [x] Promo broadcast (invite + boost presets, queued batches)
- [x] Admin affiliate management + broadcast UI

## Phase 8 — Support & Legal ✅ Done

- [x] Support tickets (user + admin)
- [x] **Terms of Service** (`/terms`)
- [x] **Privacy Policy** (`/privacy`)
- [x] **Refund Policy** (`/refund`)
- [x] Footer + register + checkout links

## Phase 9 — Email System ✅ Done

- [x] Mail Panel integration
- [x] Transactional presets (OTP, signup, subscription, expiry)
- [x] Referral bonus reminder cron
- [x] Subscription expiry reminder cron
- [x] Affiliate monthly + promo presets

---

## Optional / Future

- [ ] Go cloud proxy full webhook integration (spec in PROJECT_SPEC.md)
- [ ] Scheduled affiliate promo campaigns (currently manual admin trigger)
- [ ] User email notification preferences
- [ ] Admin CSV export on orders list
- [ ] GST/tax line items on invoices (India-specific)

---

## Run Locally

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Open: http://127.0.0.1:8000

## Production

See **[SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md)** for cron jobs, webhooks, and deploy checklist.

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=EmailPresetSeeder --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Cron (required):**

```cron
* * * * * cd /path/to/SemrushToolz && php artisan schedule:run >> /dev/null 2>&1
```

---

## Key Docs

| File | Purpose |
|------|---------|
| [PROJECT_SPEC.md](PROJECT_SPEC.md) | Full product specification |
| [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md) | Cron, webhooks, deploy checklist |
| [BUYAHREF_SETUP.md](BUYAHREF_SETUP.md) | UPI payment hub |
| [PAYPAL_SETUP.md](PAYPAL_SETUP.md) | PayPal + webhooks |
