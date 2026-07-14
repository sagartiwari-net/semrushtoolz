<?php

namespace App\Services;

use App\Models\ResellerLedger;
use App\Models\ResellerProvision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResellerCancelService
{
    public const WINDOW_MINUTES = 60;

    public function __construct(
        protected ResellerBalanceService $balances,
        protected SubscriptionService $subscriptions,
    ) {}

    public function monthlyCancelLimit(User $reseller): ?int
    {
        $limit = $reseller->resellerProfile?->monthly_cancel_limit;

        if ($limit === null || (int) $limit <= 0) {
            return null;
        }

        return (int) $limit;
    }

    public function cancellationsUsedThisMonth(User $reseller): int
    {
        return ResellerProvision::query()
            ->where('reseller_user_id', $reseller->id)
            ->whereNotNull('cancelled_at')
            ->where('cancelled_by_role', 'reseller')
            ->whereBetween('cancelled_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function remainingCancelsThisMonth(User $reseller): ?int
    {
        $limit = $this->monthlyCancelLimit($reseller);
        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->cancellationsUsedThisMonth($reseller));
    }

    /** Whether reseller may cancel this specific provision right now. */
    public function resellerCanCancel(User $reseller, ResellerProvision $provision): bool
    {
        try {
            $this->assertResellerMayCancel($reseller, $provision);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function assertResellerMayCancel(User $reseller, ResellerProvision $provision): void
    {
        if ((int) $provision->reseller_user_id !== (int) $reseller->id) {
            throw new RuntimeException('You cannot cancel this provision.');
        }

        if ($provision->password_reset) {
            throw new RuntimeException('Password reset records cannot be cancelled.');
        }

        if ($provision->cancelled_at) {
            throw new RuntimeException('This provision is already cancelled.');
        }

        if ((float) $provision->amount_charged <= 0) {
            throw new RuntimeException('Nothing to cancel for this provision.');
        }

        $limit = $this->monthlyCancelLimit($reseller);
        if ($limit === null) {
            throw new RuntimeException('Cancel access is not enabled for your account. Contact admin.');
        }

        if ($this->cancellationsUsedThisMonth($reseller) >= $limit) {
            throw new RuntimeException("Monthly cancel limit reached ({$limit} per month).");
        }

        if ($provision->created_at->lt(now()->subMinutes(self::WINDOW_MINUTES))) {
            throw new RuntimeException('Cancel is only allowed within 1 hour of provisioning.');
        }
    }

    public function assertAdminMayCancel(ResellerProvision $provision): void
    {
        if ($provision->password_reset) {
            throw new RuntimeException('Password reset records cannot be cancelled.');
        }

        if ($provision->cancelled_at) {
            throw new RuntimeException('This provision is already cancelled.');
        }

        if ((float) $provision->amount_charged <= 0 && ! $provision->subscription_id) {
            throw new RuntimeException('Nothing to cancel for this provision.');
        }
    }

    /**
     * @return array{provision: ResellerProvision, refund: float}
     */
    public function cancelByReseller(User $reseller, ResellerProvision $provision): array
    {
        $this->assertResellerMayCancel($reseller, $provision);

        return $this->performCancel($provision, $reseller, 'reseller');
    }

    /**
     * @return array{provision: ResellerProvision, refund: float}
     */
    public function cancelByAdmin(User $admin, ResellerProvision $provision): array
    {
        $this->assertAdminMayCancel($provision);

        return $this->performCancel($provision, $admin, 'admin');
    }

    /**
     * @return array{provision: ResellerProvision, refund: float}
     */
    protected function performCancel(ResellerProvision $provision, User $actor, string $role): array
    {
        return DB::transaction(function () use ($provision, $actor, $role) {
            $locked = ResellerProvision::query()->whereKey($provision->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at) {
                throw new RuntimeException('This provision is already cancelled.');
            }

            $refund = round((float) $locked->amount_charged, 2);
            $reseller = User::findOrFail($locked->reseller_user_id);

            if ($locked->subscription_id) {
                $subscription = $locked->subscription;
                if ($subscription && $subscription->status === 'active') {
                    $this->subscriptions->cancelWithoutRefund(
                        $subscription,
                        $role === 'admin' ? 'Cancelled by admin (reseller refund)' : 'Cancelled by reseller'
                    );
                }
            }

            if ($refund > 0) {
                $this->balances->credit($reseller, $refund, ResellerLedger::TYPE_CREDIT_CANCEL_REFUND, [
                    'provision_id' => $locked->id,
                    'end_user_id' => $locked->end_user_id,
                    'end_user_email' => $locked->end_user_email,
                    'tool_id' => $locked->tool_id,
                    'cancelled_by' => $actor->id,
                    'cancelled_by_role' => $role,
                ]);
            }

            $locked->update([
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $actor->id,
                'cancelled_by_role' => $role,
                'refund_amount' => $refund,
            ]);

            return [
                'provision' => $locked->fresh(['tool', 'subscription']),
                'refund' => $refund,
            ];
        });
    }
}
