# Redis (recommended for 1000+ users)

Redis speeds up sessions and cache. Laravel falls back to database if Redis is not configured.

## 1. Install Redis (aaPanel)

1. aaPanel → **App Store** → search **Redis** → Install
2. Start Redis service

## 2. Update `.env` on server

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 3. PHP Redis extension

aaPanel → PHP 8.4 → **Extensions** → enable **redis**

## 4. Apply

```bash
cd /www/wwwroot/semrushtoolz.com
/www/server/php/84/bin/php artisan optimize:clear
```

## 5. Verify

```bash
/www/server/php/84/bin/php artisan tinker --execute="Cache::put('redis_test', 'ok', 60); echo Cache::get('redis_test');"
```

Should print `ok`.

## Queue worker (with Redis)

Add aaPanel cron (every minute):

```bash
cd /www/wwwroot/semrushtoolz.com && /www/server/php/84/bin/php artisan queue:work redis --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

Until Redis is installed, keep `CACHE_STORE=database` and `SESSION_DRIVER=database` (current default).
