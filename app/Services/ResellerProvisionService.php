<?php

namespace App\Services;

use App\Models\ResellerLedger;
use App\Models\ResellerProvision;
use App\Models\Subscription;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ResellerProvisionService
{
    public function __construct(
        protected ResellerBalanceService $balances,
        protected ResellerPricingService $pricing,
        protected EmailPolicyService $emailPolicy,
    ) {}

    public function resellerCanManageUser(User $reseller, User $endUser): bool
    {
        if ((int) $endUser->created_by_reseller_id === (int) $reseller->id) {
            return true;
        }

        return ResellerProvision::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('end_user_id', $endUser->id)
            ->exists();
    }

    /**
     * @return array{
     *   provision: ResellerProvision,
     *   was_new_user: bool,
     *   plain_password: ?string,
     *   access_url: string,
     *   message: string
     * }
     */
    public function provision(User $reseller, string $email, Tool $tool, int $months): array
    {
        if (! $reseller->isActiveReseller()) {
            throw new RuntimeException('Reseller account is not active.');
        }

        $email = strtolower(trim($email));
        $emailCheck = $this->emailPolicy->validate($email);
        if (! $emailCheck['allowed']) {
            throw new RuntimeException($emailCheck['message']);
        }

        $months = max(1, min(12, $months));
        if (! in_array($months, [1, 3, 6, 12], true)) {
            throw new RuntimeException('Invalid duration. Choose 1, 3, 6, or 12 months.');
        }

        $charge = $this->pricing->chargeForMonths($reseller, $tool, $months);
        if ($charge === null) {
            throw new RuntimeException('This tool is not available at reseller pricing.');
        }

        if ($this->balances->balance($reseller) < $charge) {
            throw new RuntimeException('Insufficient balance.');
        }

        return DB::transaction(function () use ($reseller, $email, $tool, $months, $charge) {
            $existing = User::query()->where('email', $email)->first();
            $wasNew = false;
            $plainPassword = null;

            if ($existing) {
                if ($existing->isAdmin() || $existing->isReseller()) {
                    throw new RuntimeException('This email cannot receive reseller access.');
                }
                $endUser = $existing;
                // Reseller grants skip email verification — mark verified so they never sit in unverified bots.
                if (! $endUser->email_verified_at) {
                    $endUser->forceFill(['email_verified_at' => now()])->save();
                }
            } else {
                $plainPassword = $this->generatePassword();
                $endUser = User::create([
                    'name' => Str::before($email, '@') ?: 'User',
                    'email' => $email,
                    'password' => $plainPassword,
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'created_by_reseller_id' => $reseller->id,
                    'referral_code' => strtoupper(Str::random(8)),
                ]);
                $wasNew = true;
            }

            $subscription = $this->grantOrExtendTool($endUser, $tool, $months);

            $this->balances->debit($reseller, $charge, ResellerLedger::TYPE_DEBIT_PROVISION, [
                'tool_id' => $tool->id,
                'tool_slug' => $tool->slug,
                'end_user_id' => $endUser->id,
                'end_user_email' => $endUser->email,
                'duration_months' => $months,
                'subscription_id' => $subscription->id,
            ]);

            $provision = ResellerProvision::create([
                'reseller_user_id' => $reseller->id,
                'end_user_id' => $endUser->id,
                'end_user_email' => $endUser->email,
                'tool_id' => $tool->id,
                'duration_months' => $months,
                'amount_charged' => $charge,
                'was_new_user' => $wasNew,
                'password_reset' => false,
                'subscription_id' => $subscription->id,
            ]);

            return [
                'provision' => $provision->load('tool'),
                'was_new_user' => $wasNew,
                'plain_password' => $plainPassword,
                'access_url' => route('login'),
                'message' => $wasNew
                    ? 'New account created and access granted.'
                    : 'Account already exists. Access granted.',
            ];
        });
    }

    /**
     * @return array{plain_password: string, user: User, access_url: string}
     */
    public function resetPassword(User $reseller, string $email): array
    {
        $email = strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw new RuntimeException('User not found.');
        }

        if (! $this->resellerCanManageUser($reseller, $user)) {
            throw new RuntimeException('You cannot reset this user.');
        }

        if ($user->isAdmin() || $user->isReseller()) {
            throw new RuntimeException('You cannot reset this user.');
        }

        $plain = $this->generatePassword();
        $user->update(['password' => $plain]);

        ResellerProvision::create([
            'reseller_user_id' => $reseller->id,
            'end_user_id' => $user->id,
            'end_user_email' => $user->email,
            'tool_id' => ResellerProvision::query()
                ->where('reseller_user_id', $reseller->id)
                ->where('end_user_id', $user->id)
                ->whereNotNull('tool_id')
                ->latest('id')
                ->value('tool_id'),
            'duration_months' => 0,
            'amount_charged' => 0,
            'was_new_user' => false,
            'password_reset' => true,
            'subscription_id' => null,
        ]);

        return [
            'plain_password' => $plain,
            'user' => $user,
            'access_url' => route('login'),
        ];
    }

    public function grantOrExtendTool(User $user, Tool $tool, int $months): Subscription
    {
        $existing = Subscription::query()
            ->where('user_id', $user->id)
            ->where('tool_id', $tool->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        if ($existing) {
            $base = $existing->ends_at->isFuture() ? $existing->ends_at : now();
            $existing->update([
                'status' => 'active',
                'ends_at' => $base->copy()->addMonths($months),
                'duration_months' => (int) $existing->duration_months + $months,
            ]);

            return $existing->fresh();
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => null,
            'tool_id' => $tool->id,
            'status' => 'active',
            'duration_months' => $months,
            'duration_days' => null,
            'currency' => 'inr',
            'amount_paid' => 0,
            'starts_at' => now(),
            'ends_at' => now()->addMonths($months),
            'auto_renew' => false,
        ]);
    }

    public function generatePassword(int $length = 12): string
    {
        return Str::password($length, letters: true, numbers: true, symbols: false);
    }

    /** Users this reseller may see / manage. */
    public function managedUsersQuery(User $reseller)
    {
        $provisionedIds = ResellerProvision::query()
            ->where('reseller_user_id', $reseller->id)
            ->distinct()
            ->pluck('end_user_id');

        return User::query()
            ->where(function ($q) use ($reseller, $provisionedIds) {
                $q->where('created_by_reseller_id', $reseller->id)
                    ->orWhereIn('id', $provisionedIds);
            })
            ->where('role', 'user')
            ->orderByDesc('id');
    }
}
