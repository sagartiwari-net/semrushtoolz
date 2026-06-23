# PayPal Recurring — SemrushToolz Setup

USD monthly auto-renew subscriptions via PayPal Subscriptions API.

## Quick setup (Admin panel)

1. **PayPal Developer account** — https://developer.paypal.com
2. **Create App** (Sandbox pehle test ke liye)
   - Dashboard → Apps & Credentials → Sandbox → Create App
   - Copy **Client ID** + **Secret**
3. **SemrushToolz Admin**
   - Login → **Admin → Payment Hub** (Payment Integration)
   - Scroll to **PayPal Recurring** section
   - Enable PayPal, paste Client ID + Secret
   - Mode: **Sandbox** (testing) ya **Live** (production)
   - Save → **Test PayPal Connection**
4. **Webhook** (renewals ke liye zaroori production mein)
   - PayPal Developer → Webhooks → Add Webhook
   - URL: `https://yourdomain.com/webhooks/paypal`
   - Events:
     - `BILLING.SUBSCRIPTION.ACTIVATED`
     - `BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED`
     - `BILLING.SUBSCRIPTION.CANCELLED`
     - `BILLING.SUBSCRIPTION.EXPIRED`
     - `BILLING.SUBSCRIPTION.SUSPENDED`
   - Copy **Webhook ID** → Admin → PayPal section → Save

## CLI check

```bash
php artisan paypal:status
```

## Test flow (Sandbox)

1. Shop → koi tool select karo
2. Currency **USD** choose karo (PayPal sirf USD par dikhega)
3. Payment method **PayPal**
4. Place order → Subscribe button → Sandbox buyer login
   - Sandbox test accounts: Developer Dashboard → Sandbox → Accounts
5. Payment success → Tools access active

## How billing works

| Duration selected | PayPal behaviour |
|-------------------|------------------|
| 1 month | Monthly recurring until user cancels on PayPal |
| 3 / 6 / 12 month | Monthly USD (discounted per-month rate) × N cycles, then stops |

- **INR / UPI** = Buyahref Hub (alag system — touch mat karo)
- **PayPal** = USD only, recurring

## Live go-live checklist

- [ ] PayPal app switched to **Live** credentials
- [ ] Admin mode set to **Live**
- [ ] Webhook URL HTTPS + Webhook ID saved
- [ ] Test one real small subscription
- [ ] `php artisan paypal:status` passes

## Optional .env fallback

Admin panel preferred. `.env` se bhi chalega agar admin empty ho:

```env
PAYPAL_ENABLED=1
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_WEBHOOK_ID=
PAYPAL_MODE=sandbox
```

## Troubleshooting

| Problem | Fix |
|---------|-----|
| PayPal option nahi dikhta | Enable PayPal in admin + save secret |
| "authentication failed" | Wrong Client ID/Secret ya Sandbox/Live mismatch |
| Subscribe button nahi | Client ID missing; check admin settings |
| Renewal nahi ho raha | Webhook ID + events configure karo |
| Buyahref UPI broken | PayPal alag hai — UPI settings mat change karo |
