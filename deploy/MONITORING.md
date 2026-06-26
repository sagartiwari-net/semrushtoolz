# Monitoring & alerts

## 1. UptimeRobot (free)

1. Sign up at [uptimerobot.com](https://uptimerobot.com)
2. **Add Monitor** → HTTP(s)
3. URL: `https://semrushtoolz.com/up`
4. Interval: **5 minutes**
5. Alert: your email / Telegram

Laravel health route `/up` is already enabled.

## 2. Sentry (error tracking — free tier)

1. Create project at [sentry.io](https://sentry.io) → **Laravel**
2. Copy DSN
3. Add to server `.env`:

```env
SENTRY_LARAVEL_DSN=https://xxxx@xxxx.ingest.sentry.io/xxxx
SENTRY_TRACES_SAMPLE_RATE=0.2
```

4. Deploy + clear cache:

```bash
/www/server/php/84/bin/php artisan optimize:clear
```

## 3. Email alerts (fallback — no Sentry needed)

If `ADMIN_NOTIFY_EMAIL` is set, production errors also email you (max 1 per 30 min per error type):

```env
ADMIN_NOTIFY_EMAIL=your@email.com
```

Works alongside Sentry.

## 4. Disposable email blocklist sync

Runs weekly via cron (Monday 4 AM). Manual run after deploy:

```bash
/www/server/php/84/bin/php artisan email-policy:sync-disposable
```

Downloads latest temp-mail domains from public blocklists.

## 5. Logs

```bash
tail -f /www/wwwroot/semrushtoolz.com/storage/logs/laravel.log
```

Set production log level:

```env
LOG_CHANNEL=daily
LOG_LEVEL=warning
```
