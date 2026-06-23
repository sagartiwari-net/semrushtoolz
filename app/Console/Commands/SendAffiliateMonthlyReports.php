<?php

namespace App\Console\Commands;

use App\Models\EmailNotificationLog;
use App\Models\EmailPreset;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\TransactionalEmailService;
use Illuminate\Console\Command;

class SendAffiliateMonthlyReports extends Command
{
    protected $signature = 'affiliate:send-monthly-reports {--batch=8}';

    protected $description = 'Send monthly affiliate earnings reports in staggered batches (1st of month, 6 AM–12 PM)';

    public function handle(TransactionalEmailService $mail): int
    {
        if (! \App\Models\SiteSetting::affiliateConfig()['monthly_email_enabled']) {
            $this->info('Monthly affiliate emails are disabled in settings.');

            return self::SUCCESS;
        }

        $batchSize = max(1, (int) $this->option('batch'));
        $periodMonth = now()->subMonth();
        $periodKey = $periodMonth->format('Y-m');
        $referenceId = (int) str_replace('-', '', $periodKey);
        $presetKey = EmailPreset::KEY_AFFILIATE_MONTHLY_REPORT;

        $candidates = User::query()
            ->whereIn('id', function ($query) {
                $query->select('referrer_user_id')->from('affiliate_commissions')->distinct();
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user) => app(AffiliateService::class)->qualifiesForMonthlyReportEmail($user, $periodMonth));

        $sent = 0;
        $skipped = 0;

        foreach ($candidates as $user) {
            if ($sent >= $batchSize) {
                break;
            }

            if (EmailNotificationLog::alreadySent($user->id, $presetKey, 'affiliate_monthly', $referenceId)) {
                $skipped++;

                continue;
            }

            $mail->sendAffiliateMonthlyReport($user, forMonth: $periodMonth);
            $sent++;
        }

        $remaining = $candidates->filter(function (User $user) use ($presetKey, $referenceId) {
            return ! EmailNotificationLog::alreadySent($user->id, $presetKey, 'affiliate_monthly', $referenceId);
        })->count();

        $this->info("Batch complete: sent {$sent}, skipped {$skipped} already sent, {$remaining} remaining.");

        return self::SUCCESS;
    }
}
