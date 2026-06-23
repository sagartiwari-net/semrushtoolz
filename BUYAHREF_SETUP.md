# Buyahref Payment Hub — SemrushToolz Laravel

UPI payments are configured from **Admin panel** — no `.env` keys needed.

## Setup (one time)

### 1. Payment Hub (buyahref.com)

1. Open https://buyahref.com/payment/admin
2. Merchants → semrushtoolz (or create new)
3. Copy **API Key** + **API Secret**
4. Set webhook URL (shown on Laravel admin page)

### 2. SemrushToolz Admin

1. Login → **Admin → Payment Hub** (Payment Integration)
2. Enable **UPI via Buyahref**
3. Paste Hub URL, API Key, API Secret
4. Set payment window (default **5 minutes**)
5. Save

Webhook URL is displayed on that page — copy it into Payment Hub merchant settings.

### 3. Migration (if not done)

```bash
php artisan migrate
```

## Flow

```
Checkout UPI → redirect buyahref.com/payment → pay → webhook → subscription active
```

## Change credentials later

Admin → **Payment Hub** → edit → Save. No server restart, no `.env` edit.

## aMember vs Laravel

If both sites use Payment Hub, use **separate merchants** (different webhook URLs) or one merchant per domain.
