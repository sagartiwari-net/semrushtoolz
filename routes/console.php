<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:expire')->everyFiveMinutes();
Schedule::command('orders:purge-cancelled-upi')->dailyAt('04:00');
Schedule::command('subscriptions:expire')->hourly();
Schedule::command('subscriptions:send-expiry-reminders')->dailyAt('09:00');
Schedule::command('referral:send-signup-bonus-reminders')->dailyAt('10:00');
Schedule::command('affiliate:send-monthly-reports --batch=8')
    ->everyFifteenMinutes()
    ->when(fn () => now()->day === 1 && now()->hour >= 6 && now()->hour < 12);
Schedule::command('email:process-broadcast-queue --batch=8')->everyFifteenMinutes();
Schedule::command('affiliate:release-held-commissions')->daily();
Schedule::command('tools:cleanup-sessions')->everyFiveMinutes();
Schedule::command('users:purge-unverified')->dailyAt('03:00');
Schedule::command('email-policy:sync-disposable')->weeklyOn(1, '04:00');
Schedule::command('upi:verify')->everyThirtySeconds();
