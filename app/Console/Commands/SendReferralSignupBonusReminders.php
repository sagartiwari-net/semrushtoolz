<?php

namespace App\Console\Commands;

use App\Models\EmailPreset;
use App\Models\User;
use App\Services\ReferralSignupBonusService;
use App\Services\TransactionalEmailService;
use Illuminate\Console\Command;

class SendReferralSignupBonusReminders extends Command
{
    protected $signature = 'referral:send-signup-bonus-reminders';

    protected $description = 'Send referral signup bonus reminder emails to referred users who have not purchased yet';

    /** @var array<int, string> */
    private array $dayPresets = [
        0 => EmailPreset::KEY_REFERRAL_BONUS_DAY1,
        1 => EmailPreset::KEY_REFERRAL_BONUS_DAY2,
        2 => EmailPreset::KEY_REFERRAL_BONUS_DAY3,
    ];

    public function handle(TransactionalEmailService $mail, ReferralSignupBonusService $bonus): int
    {
        if (! $bonus->isEnabled()) {
            $this->info('Referral signup bonus is disabled.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($this->dayPresets as $dayOffset => $presetKey) {
            $targetDate = now()->subDays($dayOffset)->toDateString();

            User::query()
                ->whereNotNull('referred_by_user_id')
                ->whereNotNull('referral_bonus_expires_at')
                ->where('referral_bonus_expires_at', '>', now())
                ->whereDate('created_at', $targetDate)
                ->whereDoesntHave('orders', fn ($q) => $q->where('status', 'completed'))
                ->each(function (User $user) use ($mail, $presetKey, $dayOffset, &$sent) {
                    $mail->sendReferralBonusReminder($user, $presetKey, $dayOffset);
                    $sent++;
                });
        }

        $this->info("Processed {$sent} referral bonus reminder(s).");

        return self::SUCCESS;
    }
}
