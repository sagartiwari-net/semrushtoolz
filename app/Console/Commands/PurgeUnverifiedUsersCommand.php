<?php

namespace App\Console\Commands;

use App\Services\UserPurgeService;
use Illuminate\Console\Command;

class PurgeUnverifiedUsersCommand extends Command
{
    protected $signature = 'users:purge-unverified {--days= : Days since signup (default from config)} {--dry-run : List only, do not delete}';

    protected $description = 'Delete unverified user accounts older than N days with no active subscription or completed order';

    public function handle(UserPurgeService $purge): int
    {
        $days = (int) ($this->option('days') ?: config('security.purge_unverified_days', 7));

        if ($this->option('dry-run')) {
            $candidates = $purge->eligibleQuery($days)->orderBy('created_at')->get();

            if ($candidates->isEmpty()) {
                $this->info('No unverified users eligible for purge.');

                return self::SUCCESS;
            }

            $this->info("Found {$candidates->count()} unverified user(s) older than {$days} day(s).");

            foreach ($candidates as $user) {
                $this->line("  [dry-run] {$user->email} (joined {$user->created_at->toDateString()})");
            }

            return self::SUCCESS;
        }

        $result = $purge->purgeEligible('cron');
        $deleted = $result['deleted'];

        if ($deleted === 0) {
            $this->info('No unverified users eligible for purge.');

            return self::SUCCESS;
        }

        $purge->notifyAdmin($deleted, $result['emails']);

        $this->info("Deleted {$deleted} unverified account(s). Admin notified if email configured.");

        return self::SUCCESS;
    }
}
