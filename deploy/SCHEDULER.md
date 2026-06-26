# Laravel Scheduler (required for auto-purge & cron jobs)

Add this cron in aaPanel → Cron (run every minute):

```bash
* * * * * cd /www/wwwroot/semrushtoolz.com && /www/server/php/84/bin/php artisan schedule:run >> /dev/null 2>&1
```

Verify:

```bash
/www/server/php/84/bin/php artisan schedule:list
```

You should see `users:purge-unverified` daily at 03:00.

Test purge (dry run):

```bash
/www/server/php/84/bin/php artisan users:purge-unverified --dry-run
```
