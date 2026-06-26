<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AdminUserQueryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeUnverifiedUsersCommand extends Command
{
    protected $signature = 'users:purge-unverified {--days= : Days since signup (default from config)} {--dry-run : List only, do not delete}';

    protected $description = 'Delete unverified user accounts older than N days with no active subscription or completed order';

    public function handle(AdminUserQueryService $queries): int
    {
        $days = (int) ($this->option('days') ?: config('security.purge_unverified_days', 7));
        $cutoff = now()->subDays($days);

        $candidates = User::query()
            ->where('role', 'user')
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('subscriptions', fn ($s) => $queries->scopeActive($s))
            ->whereDoesntHave('orders', fn ($o) => $o->where('status', 'completed'))
            ->orderBy('created_at')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No unverified users eligible for purge.');

            return self::SUCCESS;
        }

        $this->info("Found {$candidates->count()} unverified user(s) older than {$days} day(s).");

        if ($this->option('dry-run')) {
            foreach ($candidates as $user) {
                $this->line("  [dry-run] {$user->email} (joined {$user->created_at->toDateString()})");
            }

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($candidates as $user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} unverified account(s).");

        return self::SUCCESS;
    }
}
