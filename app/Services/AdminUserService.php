<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ReferralClick;
use App\Models\User;
use App\Models\UserLoginLog;

class AdminUserService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected SecurityMonitorService $security,
        protected UserSessionService $sessions,
        protected AffiliateService $affiliates,
    ) {}

    public function profile(User $user): array
    {
        $user->load(['referrer', 'subscriptions.plan', 'subscriptions.tool']);

        $securitySummary = $this->security->getUserIpSummary($user->id);
        $activeSessions = $this->sessions
            ->activeSessions($user->id)
            ->map(fn ($session) => $this->sessions->formatSessionRow($session));

        $recentLogs = UserLoginLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('logged_at')
            ->limit(50)
            ->get();

        $uniqueDevices = $recentLogs->pluck('device_fingerprint')->filter()->unique()->count();
        $uniqueIpsWeek = $securitySummary['unique_ips'];

        $affiliateStats = $this->affiliates->statsFor($user);
        $referrals = User::where('referred_by_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'name', 'email', 'created_at', 'status']);

        $commissions = AffiliateCommission::with(['referred', 'order'])
            ->where('referrer_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $payouts = AffiliatePayout::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $referralClicks = ReferralClick::query()
            ->where('referral_code', $user->referral_code)
            ->orderByDesc('clicked_at')
            ->limit(15)
            ->get();

        $referredBy = $user->referrer;
        $wasReferred = AffiliateCommission::with('referrer')
            ->where('referred_user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        return [
            'user' => $user,
            'activeSubscriptions' => $this->subscriptions->activeSubscriptions($user),
            'subscriptionHistory' => $user->subscriptions()->with(['plan', 'tool'])->orderByDesc('created_at')->limit(20)->get(),
            'recentOrders' => Order::with(['plan', 'tool'])
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(),
            'security' => $securitySummary,
            'uniqueDevicesWeek' => $uniqueDevices,
            'uniqueIpsWeek' => $uniqueIpsWeek,
            'activeSessions' => $activeSessions,
            'accessLogs' => $recentLogs,
            'openAlerts' => $user->securityAlerts()->where('status', 'open')->orderByDesc('created_at')->get(),
            'affiliate' => $affiliateStats,
            'referrals' => $referrals,
            'commissions' => $commissions,
            'payouts' => $payouts,
            'referralClicks' => $referralClicks,
            'referredBy' => $referredBy,
            'referralCommission' => $wasReferred,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    public function grantSubscription(User $user, Plan $plan, int $durationMonths, ?int $durationDays = null): \App\Models\Subscription
    {
        $endsAt = $durationDays
            ? now()->addDays($durationDays)
            : now()->addMonths(max(1, $durationMonths));

        return \App\Models\Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'duration_months' => $durationDays ? 0 : $durationMonths,
            'duration_days' => $durationDays,
            'currency' => 'inr',
            'amount_paid' => 0,
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'auto_renew' => false,
        ]);
    }

    public function extendSubscription(\App\Models\Subscription $subscription, int $months = 0, int $days = 0): void
    {
        $base = $subscription->ends_at->isFuture() ? $subscription->ends_at : now();

        if ($days > 0) {
            $endsAt = $base->copy()->addDays($days);
        } else {
            $endsAt = $base->copy()->addMonths(max(1, $months));
        }

        $subscription->update([
            'status' => 'active',
            'ends_at' => $endsAt,
        ]);
    }

    public function cancelSubscription(\App\Models\Subscription $subscription): void
    {
        $this->subscriptions->cancelWithoutRefund($subscription);
    }
}
