<?php

namespace App\Console\Commands;

use App\Models\EmailPreset;
use App\Models\Subscription;
use App\Services\TransactionalEmailService;
use Illuminate\Console\Command;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:send-expiry-reminders';

    protected $description = 'Send plan expiry reminder emails (before and after expiration)';

    /** @var array<int, string> */
    private array $beforeExpiry = [
        7 => EmailPreset::KEY_PLAN_EXPIRE_7D,
        3 => EmailPreset::KEY_PLAN_EXPIRE_3D,
        2 => EmailPreset::KEY_PLAN_EXPIRE_2D,
        0 => EmailPreset::KEY_PLAN_EXPIRE_TODAY,
    ];

    /** @var array<int, string> */
    private array $afterExpiry = [
        1 => EmailPreset::KEY_PLAN_EXPIRED_1D,
        2 => EmailPreset::KEY_PLAN_EXPIRED_2D,
        3 => EmailPreset::KEY_PLAN_EXPIRED_3D,
        7 => EmailPreset::KEY_PLAN_EXPIRED_1W,
        14 => EmailPreset::KEY_PLAN_EXPIRED_2W,
    ];

    public function handle(TransactionalEmailService $mail): int
    {
        $sent = 0;

        foreach ($this->beforeExpiry as $days => $presetKey) {
            $sent += $this->sendForActiveEndingOn($mail, $days, $presetKey);
        }

        foreach ($this->afterExpiry as $daysAgo => $presetKey) {
            $sent += $this->sendForExpiredOn($mail, $daysAgo, $presetKey);
        }

        $this->info("Queued/sent {$sent} expiry reminder email(s).");

        return self::SUCCESS;
    }

    private function sendForActiveEndingOn(TransactionalEmailService $mail, int $days, string $presetKey): int
    {
        $targetDate = now()->addDays($days)->toDateString();
        $count = 0;

        Subscription::query()
            ->with(['user', 'plan', 'tool'])
            ->where('status', 'active')
            ->whereDate('ends_at', $targetDate)
            ->each(function (Subscription $subscription) use ($mail, $presetKey, &$count) {
                $mail->sendPlanExpiryReminder($subscription, $presetKey);
                $count++;
            });

        return $count;
    }

    private function sendForExpiredOn(TransactionalEmailService $mail, int $daysAgo, string $presetKey): int
    {
        $targetDate = now()->subDays($daysAgo)->toDateString();
        $count = 0;

        Subscription::query()
            ->with(['user', 'plan', 'tool'])
            ->where('status', 'expired')
            ->whereDate('ends_at', $targetDate)
            ->each(function (Subscription $subscription) use ($mail, $presetKey, &$count) {
                $mail->sendPlanExpiryReminder($subscription, $presetKey);
                $count++;
            });

        return $count;
    }
}
