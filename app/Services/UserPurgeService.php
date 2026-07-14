<?php

namespace App\Services;

use App\Mail\AdminPurgeSummaryMail;
use App\Models\User;
use App\Models\UserPurgeLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserPurgeService
{
    public function __construct(
        protected AdminUserQueryService $queries,
    ) {}

    public function eligibleQuery(?int $days = null): Builder
    {
        $days = $days ?? (int) config('security.purge_unverified_days', 7);
        $cutoff = now()->subDays($days);

        return User::query()
            ->where('role', 'user')
            ->whereNull('email_verified_at')
            ->whereNull('created_by_reseller_id')
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('subscriptions', fn ($s) => $this->queries->scopeActive($s))
            ->whereDoesntHave('orders', fn ($o) => $o->where('status', 'completed'));
    }

    public function canPurge(User $user, bool $requireEligibleAge = true): bool
    {
        if ($user->role !== 'user' || $user->email_verified_at || $user->created_by_reseller_id) {
            return false;
        }

        if ($user->orders()->where('status', 'completed')->exists()) {
            return false;
        }

        if ($user->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->exists()) {
            return false;
        }

        if ($requireEligibleAge) {
            $cutoff = now()->subDays((int) config('security.purge_unverified_days', 7));

            return $user->created_at <= $cutoff;
        }

        return true;
    }

    public function purgeUser(User $user, string $reason, string $triggeredBy = 'manual'): bool
    {
        $requireAge = $triggeredBy === 'cron';

        if (! $this->canPurge($user, $requireAge)) {
            return false;
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();

        UserPurgeLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'reason' => $reason,
            'triggered_by' => $triggeredBy,
            'purged_at' => now(),
        ]);

        $user->delete();

        return true;
    }

    /** @return array{deleted: int, emails: list<string>} */
    public function purgeEligible(string $triggeredBy = 'cron'): array
    {
        $candidates = $this->eligibleQuery()->orderBy('created_at')->get();
        $emails = [];

        foreach ($candidates as $user) {
            $emails[] = $user->email;
            $this->purgeUser($user, 'unverified_auto', $triggeredBy);
        }

        return ['deleted' => count($emails), 'emails' => $emails];
    }

    public function notifyAdmin(int $deleted, array $emails): void
    {
        if ($deleted === 0) {
            return;
        }

        $to = config('security.purge_notify_email');
        if (! $to) {
            return;
        }

        Mail::to($to)->send(new AdminPurgeSummaryMail($deleted, $emails));
    }
}
