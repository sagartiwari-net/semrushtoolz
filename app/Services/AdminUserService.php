<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Tool;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Support\TablePageSize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminUserService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected SecurityMonitorService $security,
        protected UserSessionService $sessions,
        protected AffiliateService $affiliates,
    ) {}

    public function profile(User $user, ?Request $request = null): array
    {
        $request ??= request();

        $user->load(['referrer', 'subscriptions.plan', 'subscriptions.tool']);

        $tab = $request->query('tab', 'account');
        $affiliateTab = $request->query('affiliate_tab', 'referrals');
        $perPage = TablePageSize::resolve($request);

        $securitySummary = $this->security->getUserIpSummary($user->id);
        $activeSessions = $this->sessions
            ->activeSessions($user->id)
            ->map(fn ($session) => $this->sessions->formatSessionRow($session));

        $recentLogsForDevices = UserLoginLog::query()
            ->where('user_id', $user->id)
            ->where('logged_at', '>=', now()->subDays(7))
            ->orderByDesc('logged_at')
            ->limit(100)
            ->get();

        $uniqueDevicesWeek = $recentLogsForDevices
            ->pluck('device_fingerprint')
            ->filter()
            ->unique()
            ->count();

        $affiliateStats = $this->affiliates->statsFor($user);
        $referredBy = $user->referrer;
        $wasReferred = AffiliateCommission::with('referrer')
            ->where('referred_user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        $activeSubscriptions = $this->subscriptions->activeSubscriptions($user);

        $subscriptionHistory = $this->emptyPaginator();
        $accessLogs = $this->emptyPaginator();
        $orders = $this->emptyPaginator();
        $referrals = $this->emptyPaginator();
        $commissions = $this->emptyPaginator();
        $payouts = $this->emptyPaginator();

        if ($tab === 'subscriptions') {
            $subscriptionHistory = $user->subscriptions()
                ->with(['plan', 'tool'])
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        if ($tab === 'activity') {
            $accessLogs = UserLoginLog::query()
                ->where('user_id', $user->id)
                ->orderByDesc('logged_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        if ($tab === 'orders') {
            $orders = Order::with(['plan', 'tool', 'subscription'])
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        if ($tab === 'affiliate') {
            match ($affiliateTab) {
                'commissions' => $commissions = AffiliateCommission::with(['referred', 'order'])
                    ->where('referrer_user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->paginate($perPage)
                    ->withQueryString(),
                'payouts' => $payouts = AffiliatePayout::where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->paginate($perPage)
                    ->withQueryString(),
                default => $referrals = User::where('referred_by_user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->paginate($perPage, ['id', 'name', 'email', 'created_at', 'status'])
                    ->withQueryString(),
            };
        }

        return [
            'user' => $user,
            'tab' => $tab,
            'affiliateTab' => $affiliateTab,
            'perPage' => $perPage,
            'activeSubscriptions' => $activeSubscriptions,
            'subscriptionHistory' => $subscriptionHistory,
            'orders' => $orders,
            'security' => $securitySummary,
            'uniqueDevicesWeek' => $uniqueDevicesWeek,
            'uniqueIpsWeek' => $securitySummary['unique_ips'],
            'activeSessions' => $activeSessions,
            'accessLogs' => $accessLogs,
            'openAlerts' => $user->securityAlerts()->where('status', 'open')->orderByDesc('created_at')->get(),
            'affiliate' => $affiliateStats,
            'referrals' => $referrals,
            'commissions' => $commissions,
            'payouts' => $payouts,
            'referredBy' => $referredBy,
            'referralCommission' => $wasReferred,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'shopTools' => Tool::query()
                ->where('is_active', true)
                ->where('show_in_shop', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'price_inr', 'grants_tool_slugs'])
                ->reject(fn (Tool $tool) => $tool->isPackageGrant())
                ->values(),
            'ordersCount' => Order::where('user_id', $user->id)->count(),
            'accessLogsCount' => UserLoginLog::where('user_id', $user->id)->count(),
            'referralsCount' => User::where('referred_by_user_id', $user->id)->count(),
        ];
    }

    protected function emptyPaginator(): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            Collection::make(),
            0,
            TablePageSize::DEFAULT,
            1,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function grantSubscription(User $user, Plan $plan, int $durationMonths, ?int $durationDays = null): \App\Models\Subscription
    {
        $endsAt = $durationDays
            ? now()->addDays($durationDays)
            : now()->addMonths(max(1, $durationMonths));

        return \App\Models\Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'tool_id' => null,
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

    public function grantToolSubscription(User $user, Tool $tool, int $durationMonths, ?int $durationDays = null): \App\Models\Subscription
    {
        $endsAt = $durationDays
            ? now()->addDays($durationDays)
            : now()->addMonths(max(1, $durationMonths));

        return \App\Models\Subscription::create([
            'user_id' => $user->id,
            'plan_id' => null,
            'tool_id' => $tool->id,
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
