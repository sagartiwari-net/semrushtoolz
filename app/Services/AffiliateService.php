<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\Order;
use App\Models\ReferralClick;
use App\Models\User;
use App\Support\AffiliateReportFilters;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class AffiliateService
{
    public function monthlyEarningsForUser(User $user, Carbon $month): float
    {
        return (float) AffiliateCommission::where('referrer_user_id', $user->id)
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->whereNotIn('status', ['rejected', 'reversed'])
            ->sum('amount');
    }

    public function qualifiesForMonthlyReportEmail(User $user, Carbon $forMonth): bool
    {
        $settings = $this->config();
        $minPayable = (float) $settings['monthly_email_min_payable'];

        if ($this->availablePayoutBalance($user) <= $minPayable) {
            return false;
        }

        return $this->monthlyEarningsForUser($user, $forMonth) > 0;
    }

    public function affiliatePromoDataForUser(User $user): array
    {
        $settings = $this->config();
        $monthStart = now()->startOfMonth();
        $monthEarnings = $this->monthEarningsForUser($user, $monthStart);
        $totalEarned = (float) AffiliateCommission::where('referrer_user_id', $user->id)
            ->whereIn('status', ['approved', 'paid', 'held'])
            ->sum('amount');

        $bonusNote = $settings['signup_bonus_enabled']
            ? 'New users you refer get '.(int) ($settings['signup_bonus_percent'] * 100).'% off their first month.'
            : '';

        return [
            'name' => $user->name,
            'referral_code' => $user->referral_code ?? '',
            'referral_link' => url('/?ref='.$user->referral_code),
            'commission_rate' => (string) (int) ($settings['commission_rate'] * 100),
            'month_label' => $monthStart->format('F Y'),
            'month_earnings' => $this->formatMoney($monthEarnings, 'inr'),
            'total_earned' => $this->formatMoney($totalEarned, 'inr'),
            'total_payable' => $this->formatMoney($this->availablePayoutBalance($user), 'inr'),
            'affiliates_url' => route('dashboard.affiliates'),
            'program_intro' => 'Earn '.(int) ($settings['commission_rate'] * 100).'% commission on every purchase made through your unique referral link.',
            'signup_bonus_note' => $bonusNote,
            'growth_tip' => $monthEarnings > 0
                ? 'You have already earned '.$this->formatMoney($monthEarnings, 'inr').' this month — share your link on WhatsApp and social media to grow even faster.'
                : 'Share your referral link with friends, on social media, and in relevant communities to start earning commissions.',
        ];
    }

    public function createCommissionForOrder(Order $order): ?AffiliateCommission
    {
        $order->loadMissing('user');
        $user = $order->user;

        if (! $user->referred_by_user_id) {
            return null;
        }

        if (AffiliateCommission::where('order_id', $order->id)->exists()) {
            return AffiliateCommission::where('order_id', $order->id)->first();
        }

        $settings = \App\Models\SiteSetting::affiliateConfig();
        $rate = $settings['commission_rate'];
        $amount = round($order->total * $rate, 2);
        $isPayPal = $order->payment_method === 'paypal';

        $status = 'pending';
        $holdUntil = null;

        if ($settings['auto_approve']) {
            if ($isPayPal) {
                $status = 'held';
                $holdUntil = now()->addMonths($settings['paypal_hold_months']);
            } else {
                $status = 'approved';
            }
        }

        return AffiliateCommission::create([
            'referrer_user_id' => $user->referred_by_user_id,
            'referred_user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'rate' => $rate * 100,
            'status' => $status,
            'hold_until' => $holdUntil,
        ]);
    }

    public function statsFor(User $user): array
    {
        $settings = \App\Models\SiteSetting::affiliateConfig();
        $commissions = AffiliateCommission::where('referrer_user_id', $user->id)->get();
        $referrals = User::where('referred_by_user_id', $user->id)->count();
        $converted = $commissions->count();

        $totalEarned = $commissions->whereIn('status', ['approved', 'paid', 'held', 'wallet_transferred'])->sum('amount');
        $heldAmount = $commissions->where('status', 'held')->sum('amount');
        $walletTransferred = $commissions->where('status', 'wallet_transferred')->sum('amount');
        $pendingBalance = $this->availablePayoutBalance($user);
        $paidOut = AffiliatePayout::where('user_id', $user->id)
            ->where('status', AffiliatePayout::STATUS_PROCESSED)
            ->sum('amount');

        $pendingPayoutRequest = AffiliatePayout::where('user_id', $user->id)
            ->where('status', AffiliatePayout::STATUS_PENDING)
            ->exists();

        return [
            'referrals' => $referrals,
            'converted' => $converted,
            'total_earned' => $totalEarned,
            'pending_payout' => $pendingBalance,
            'paid_out' => $paidOut,
            'commission_rate' => (int) ($settings['commission_rate'] * 100),
            'min_payout' => $settings['min_payout'],
            'has_pending_request' => $pendingPayoutRequest,
            'held_amount' => $heldAmount,
            'wallet_transferred' => $walletTransferred,
            'paypal_hold_months' => $settings['paypal_hold_months'],
            'total_earned_label' => $this->formatMoney((float) $totalEarned, 'inr'),
            'pending_payout_label' => $this->formatMoney((float) $pendingBalance, 'inr'),
            'paid_out_label' => $this->formatMoney((float) $paidOut, 'inr'),
        ];
    }

    public function availablePayoutBalance(User $user): float
    {
        $approved = AffiliateCommission::where('referrer_user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $reserved = AffiliatePayout::where('user_id', $user->id)
            ->where('status', AffiliatePayout::STATUS_PENDING)
            ->sum('amount');

        return max(0, round((float) $approved - (float) $reserved, 2));
    }

    public function requestPayout(User $user, string $method, string $detail): AffiliatePayout
    {
        $settings = \App\Models\SiteSetting::affiliateConfig();
        $balance = $this->availablePayoutBalance($user);

        if ($balance < $settings['min_payout']) {
            throw new \RuntimeException('Minimum payout is ₹'.$settings['min_payout'].'.');
        }

        if (AffiliatePayout::where('user_id', $user->id)->where('status', AffiliatePayout::STATUS_PENDING)->exists()) {
            throw new \RuntimeException('You already have a pending payout request.');
        }

        return AffiliatePayout::create([
            'user_id' => $user->id,
            'amount' => $balance,
            'method' => $method,
            'payout_detail' => $detail,
            'status' => AffiliatePayout::STATUS_PENDING,
        ]);
    }

    public function processPayout(AffiliatePayout $payout, User $admin, ?string $note = null): void
    {
        $payout->update([
            'status' => AffiliatePayout::STATUS_PROCESSED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'admin_note' => $note,
        ]);

        $remaining = (float) $payout->amount;
        $commissions = AffiliateCommission::where('referrer_user_id', $payout->user_id)
            ->where('status', 'approved')
            ->orderBy('id')
            ->get();

        foreach ($commissions as $commission) {
            if ($remaining <= 0) {
                break;
            }

            $commission->update(['status' => 'paid']);
            $remaining -= (float) $commission->amount;
        }
    }

    public function rejectPayout(AffiliatePayout $payout, User $admin, ?string $note = null): void
    {
        $payout->update([
            'status' => AffiliatePayout::STATUS_REJECTED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'admin_note' => $note,
        ]);
    }

    public function adminStats(): array
    {
        $settings = \App\Models\SiteSetting::affiliateConfig();

        $activeAffiliates = User::whereIn('id', function ($query) {
            $query->select('referrer_user_id')
                ->from('affiliate_commissions')
                ->distinct();
        })->count();

        $pendingTotal = AffiliatePayout::where('status', AffiliatePayout::STATUS_PENDING)->sum('amount');
        $pendingCommissions = AffiliateCommission::where('status', 'pending')->count();
        $totalPaid = AffiliateCommission::where('status', 'paid')->sum('amount');

        return [
            'active_affiliates' => $activeAffiliates,
            'pending_payouts' => $pendingTotal,
            'pending_commissions' => $pendingCommissions,
            'total_paid' => $totalPaid,
            'commission_rate' => (int) ($settings['commission_rate'] * 100),
            'min_payout' => $settings['min_payout'],
        ];
    }

    public function adminCommissions(?string $status = null, int $limit = 100): array
    {
        $query = AffiliateCommission::with(['referrer', 'referred', 'order.plan', 'order.tool'])
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get()->map(fn (AffiliateCommission $c) => [
            'id' => $c->id,
            'referrer' => $c->referrer->name,
            'referred' => $c->referred->name,
            'plan' => $c->order?->purchasedItemName() ?? '—',
            'order' => $c->order?->order_number ?? '—',
            'amount' => $this->formatMoney((float) $c->amount, $c->order?->currency ?? 'inr'),
            'rate' => (int) $c->rate.'%',
            'status' => ucfirst($c->status),
            'status_raw' => $c->status,
            'date' => $c->created_at->format('M d, Y'),
        ])->all();
    }

    public function topAffiliates(int $limit = 10): array
    {
        return User::query()
            ->whereIn('id', AffiliateCommission::select('referrer_user_id')->distinct())
            ->withCount(['referrals', 'affiliateCommissions'])
            ->withSum(['affiliateCommissions as earned' => fn ($q) => $q->whereIn('status', ['approved', 'paid'])], 'amount')
            ->orderByDesc('earned')
            ->limit($limit)
            ->get()
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'referrals' => $u->referrals_count,
                'commissions' => $u->affiliate_commissions_count,
                'earned' => $this->formatMoney((float) ($u->earned ?? 0), 'inr'),
                'code' => $u->referral_code,
            ])
            ->all();
    }

    public function approveCommission(AffiliateCommission $commission): void
    {
        if ($commission->status !== 'pending') {
            throw new \RuntimeException('Only pending commissions can be approved.');
        }

        $commission->loadMissing('order');
        $settings = \App\Models\SiteSetting::affiliateConfig();

        if ($commission->order?->payment_method === 'paypal') {
            $commission->update([
                'status' => 'held',
                'hold_until' => now()->addMonths($settings['paypal_hold_months']),
            ]);

            return;
        }

        $commission->update(['status' => 'approved', 'hold_until' => null]);
    }

    public function rejectCommission(AffiliateCommission $commission): void
    {
        if ($commission->status !== 'pending') {
            throw new \RuntimeException('Only pending commissions can be rejected.');
        }

        $commission->update(['status' => 'rejected']);
    }

    public function reverseCommissionForOrder(Order $order): void
    {
        $commission = AffiliateCommission::where('order_id', $order->id)->first();

        if (! $commission || in_array($commission->status, ['reversed', 'rejected'], true)) {
            return;
        }

        $commission->update([
            'status' => 'reversed',
            'reversed_at' => now(),
            'hold_until' => null,
        ]);
    }

    public function releaseHeldCommissions(): int
    {
        $released = 0;

        AffiliateCommission::query()
            ->where('status', 'held')
            ->whereNotNull('hold_until')
            ->where('hold_until', '<=', now())
            ->each(function (AffiliateCommission $commission) use (&$released) {
                $commission->update(['status' => 'approved', 'hold_until' => null]);
                $released++;
            });

        return $released;
    }

    public function monthlyStatement(User $user, ?\Illuminate\Support\Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $monthStart = $asOf->copy()->startOfMonth();
        $settings = $this->config();

        $monthEarnings = (float) AffiliateCommission::where('referrer_user_id', $user->id)
            ->whereBetween('created_at', [$monthStart, $asOf])
            ->whereNotIn('status', ['rejected', 'reversed'])
            ->sum('amount');

        $monthPayouts = (float) AffiliatePayout::where('user_id', $user->id)
            ->where('status', AffiliatePayout::STATUS_PROCESSED)
            ->whereBetween('processed_at', [$monthStart, $asOf])
            ->sum('amount');

        $approvedBefore = (float) AffiliateCommission::where('referrer_user_id', $user->id)
            ->where('created_at', '<', $monthStart)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $paidOutBefore = (float) AffiliatePayout::where('user_id', $user->id)
            ->where('status', AffiliatePayout::STATUS_PROCESSED)
            ->where('processed_at', '<', $monthStart)
            ->sum('amount');

        $carriedBalance = max(0, round($approvedBefore - $paidOutBefore, 2));
        $heldAmount = (float) AffiliateCommission::where('referrer_user_id', $user->id)
            ->where('status', 'held')
            ->sum('amount');
        $totalPayable = $this->availablePayoutBalance($user);

        return [
            'period_label' => $monthStart->format('F Y'),
            'as_of' => $asOf->format('d M Y'),
            'month_earnings' => $this->formatMoney($monthEarnings, 'inr'),
            'month_payouts' => $this->formatMoney($monthPayouts, 'inr'),
            'carried_balance' => $this->formatMoney($carriedBalance, 'inr'),
            'held_amount' => $this->formatMoney($heldAmount, 'inr'),
            'total_payable' => $this->formatMoney($totalPayable, 'inr'),
            'paypal_hold_note' => $heldAmount > 0
                ? 'PayPal commissions are on hold for '.$settings['paypal_hold_months'].' months due to PayPal refund policy.'
                : '',
        ];
    }

    protected function config(): array
    {
        return \App\Models\SiteSetting::affiliateConfig();
    }

    public function paginatedCommissionsForUser(User $user, int $perPage)
    {
        return AffiliateCommission::with(['referred', 'order.plan', 'order.tool'])
            ->where('referrer_user_id', $user->id)
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($c) => [
                'user' => $this->maskName($c->referred->name),
                'date' => $c->created_at->format('M d, Y'),
                'plan' => $c->order?->purchasedItemName() ?? '—',
                'amount' => $this->formatMoney((float) $c->amount, $c->order?->currency ?? 'inr'),
                'status' => $this->commissionStatusLabel($c),
                'status_raw' => $c->status,
                'hold_note' => $c->status === 'held' && $c->hold_until
                    ? 'On hold until '.$c->hold_until->format('d M Y').' (PayPal refund policy)'
                    : null,
            ]);
    }

    public function paginatedPayoutHistory(User $user, int $perPage)
    {
        return AffiliatePayout::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AffiliatePayout $p) => [
                'amount' => $this->formatMoney((float) $p->amount, 'inr'),
                'method' => strtoupper($p->method),
                'status' => ucfirst($p->status),
                'status_raw' => $p->status,
                'date' => $p->created_at->format('M d, Y'),
                'processed' => $p->processed_at?->format('M d, Y') ?? '—',
            ]);
    }

    public function paginatedActivity(User $user, int $perPage)
    {
        $events = collect($this->activityFeedForUser($user, 500));
        $page = max(1, (int) request()->query('page', 1));

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $events->forPage($page, $perPage)->values(),
            $events->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function paginatedAdminCommissions(?string $status, int $perPage)
    {
        $query = AffiliateCommission::with(['referrer', 'referred', 'order.plan', 'order.tool'])->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString()->through(fn (AffiliateCommission $c) => [
            'id' => $c->id,
            'referrer' => $c->referrer->name,
            'referred' => $c->referred->name,
            'plan' => $c->order?->purchasedItemName() ?? '—',
            'order' => $c->order?->order_number ?? '—',
            'payment_method' => strtoupper($c->order?->payment_method ?? '—'),
            'amount' => $this->formatMoney((float) $c->amount, $c->order?->currency ?? 'inr'),
            'status' => $this->commissionStatusLabel($c),
            'status_raw' => $c->status,
            'hold_until' => $c->hold_until?->format('d M Y'),
            'date' => $c->created_at->format('M d, Y'),
        ]);
    }

    public function paginatedAdminPayouts(int $perPage)
    {
        return AffiliatePayout::with('user')
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AffiliatePayout $p) => [
                'id' => $p->id,
                'user' => $p->user->name,
                'amount' => '₹'.number_format($p->amount, 0),
                'method' => strtoupper($p->method),
                'status' => ucfirst($p->status),
                'status_raw' => $p->status,
                'requested' => $p->created_at->format('M d, Y'),
                'processed' => $p->processed_at?->format('M d, Y') ?? '—',
            ]);
    }

    protected function commissionStatusLabel(AffiliateCommission $commission): string
    {
        if ($commission->status === 'held') {
            return 'On Hold (PayPal)';
        }

        if ($commission->status === 'wallet_transferred') {
            return 'Transferred to Wallet';
        }

        return ucfirst($commission->status);
    }

    public function payoutHistoryForUser(User $user, int $limit = 20): array
    {
        return AffiliatePayout::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (AffiliatePayout $p) => [
                'amount' => $this->formatMoney((float) $p->amount, 'inr'),
                'method' => strtoupper($p->method),
                'detail' => $p->payout_detail,
                'status' => ucfirst($p->status),
                'status_raw' => $p->status,
                'date' => $p->created_at->format('M d, Y'),
                'processed' => $p->processed_at?->format('M d, Y') ?? '—',
                'note' => $p->admin_note,
            ])
            ->all();
    }

    public function formatMoney(float $amount, string $currency = 'inr'): string
    {
        $currency = strtolower($currency);

        if ($currency === 'usd') {
            return '$'.number_format($amount, $amount < 100 ? 2 : 0);
        }

        return '₹'.number_format($amount, 0);
    }

    public function pendingPayouts(int $limit = 50): array
    {
        return AffiliatePayout::with('user')
            ->where('status', AffiliatePayout::STATUS_PENDING)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (AffiliatePayout $p) => [
                'id' => $p->id,
                'user' => $p->user->name,
                'amount' => '₹'.number_format($p->amount, 0),
                'method' => strtoupper($p->method),
                'detail' => $p->payout_detail,
                'requested' => $p->created_at->format('M d'),
            ])
            ->all();
    }

    public function recentCommissions(User $user, int $limit = 20): array
    {
        return AffiliateCommission::with(['referred', 'order.plan', 'order.tool'])
            ->where('referrer_user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($c) => [
                'user' => $this->maskName($c->referred->name),
                'date' => $c->created_at->format('M d'),
                'plan' => $c->order?->purchasedItemName() ?? '—',
                'amount' => $this->formatMoney((float) $c->amount, $c->order?->currency ?? 'inr'),
                'status' => ucfirst($c->status),
            ])
            ->all();
    }

    public function allPayoutsForAdmin(int $limit = 100): array
    {
        return AffiliatePayout::with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (AffiliatePayout $p) => [
                'id' => $p->id,
                'user' => $p->user->name,
                'amount' => '₹'.number_format($p->amount, 0),
                'method' => strtoupper($p->method),
                'status' => ucfirst($p->status),
                'status_raw' => $p->status,
                'requested' => $p->created_at->format('M d, Y'),
                'processed' => $p->processed_at?->format('M d, Y') ?? '—',
            ])
            ->all();
    }

    public function reportForUser(User $user, ?AffiliateReportFilters $filters = null): array
    {
        $filters ??= AffiliateReportFilters::fromRequest(request());
        $breakdown = $filters->view === 'monthly'
            ? $this->monthlyBreakdownForUser($user, $filters->from, $filters->to, $filters->hideEmpty)
            : $this->dailyBreakdownForUser($user, $filters->from, $filters->to, $filters->hideEmpty);

        return [
            'summary' => $this->summaryForUserInRange($user, $filters->from, $filters->to),
            'view' => $filters->view,
            'period_label' => $filters->periodLabel(),
            'breakdown_type' => $filters->view,
            'breakdown' => $breakdown,
            'activity' => $this->activityFeedForUser($user, 50),
        ];
    }

    public function adminReport(?AffiliateReportFilters $filters = null): array
    {
        $filters ??= AffiliateReportFilters::fromRequest(request());
        $breakdown = $filters->view === 'monthly'
            ? $this->monthlyBreakdownAdmin($filters->from, $filters->to, $filters->hideEmpty)
            : $this->dailyBreakdownAdmin($filters->from, $filters->to, $filters->hideEmpty);

        return [
            'summary' => $this->summaryAdminInRange($filters->from, $filters->to),
            'view' => $filters->view,
            'period_label' => $filters->periodLabel(),
            'breakdown_type' => $filters->view,
            'breakdown' => $breakdown,
            'top_codes' => ReferralClick::query()
                ->selectRaw('referral_code, count(*) as clicks')
                ->whereBetween('clicked_at', [$filters->from, $filters->to])
                ->groupBy('referral_code')
                ->orderByDesc('clicks')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['code' => $row->referral_code, 'clicks' => $row->clicks])
                ->all(),
        ];
    }

    public function paginatedReportBreakdown(array $rows, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));

        return new LengthAwarePaginator(
            collect($rows)->forPage($page, $perPage)->values()->all(),
            count($rows),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    protected function summaryForUserInRange(User $user, Carbon $from, Carbon $to): array
    {
        $referralIds = User::where('referred_by_user_id', $user->id)->pluck('id');

        $clicks = ReferralClick::where('referrer_user_id', $user->id)
            ->whereBetween('clicked_at', [$from, $to])
            ->count();

        $signups = User::where('referred_by_user_id', $user->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $commissions = AffiliateCommission::with('order')
            ->where('referrer_user_id', $user->id)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $conversions = $commissions->count();
        $revenue = $commissions->sum(fn ($c) => (float) ($c->order?->total ?? 0));

        $refunds = Order::whereIn('user_id', $referralIds)
            ->where('status', 'refunded')
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        $clickRate = $clicks > 0 ? round(($signups / $clicks) * 100, 1) : 0;
        $conversionRate = $signups > 0 ? round(($conversions / $signups) * 100, 1) : 0;

        return [
            'clicks' => $clicks,
            'signups' => $signups,
            'conversions' => $conversions,
            'click_to_signup' => $clickRate.'%',
            'signup_to_sale' => $conversionRate.'%',
            'revenue' => $this->formatMoney((float) $revenue, 'inr'),
            'refunds' => $refunds->count(),
            'refund_amount' => $this->formatMoney((float) $refunds->sum('total'), 'inr'),
        ];
    }

    protected function summaryAdminInRange(Carbon $from, Carbon $to): array
    {
        $clicks = ReferralClick::whereBetween('clicked_at', [$from, $to])->count();
        $signups = User::whereNotNull('referred_by_user_id')->whereBetween('created_at', [$from, $to])->count();
        $commissions = AffiliateCommission::with('order')->whereBetween('created_at', [$from, $to])->get();
        $revenue = $commissions->sum(fn ($c) => (float) ($c->order?->total ?? 0));

        $refundedOrders = Order::where('status', 'refunded')
            ->whereHas('user', fn ($q) => $q->whereNotNull('referred_by_user_id'))
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        return [
            'clicks' => $clicks,
            'signups' => $signups,
            'conversions' => $commissions->count(),
            'revenue' => $this->formatMoney((float) $revenue, 'inr'),
            'commissions_paid' => $this->formatMoney((float) $commissions->where('status', 'paid')->sum('amount'), 'inr'),
            'refunds' => $refundedOrders->count(),
            'refund_amount' => $this->formatMoney((float) $refundedOrders->sum('total'), 'inr'),
        ];
    }

    protected function monthlyBreakdownForUser(User $user, Carbon $from, Carbon $to, bool $hideEmpty): array
    {
        $rows = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $rangeStart = $cursor->copy()->max($from);
            $rangeEnd = $cursor->copy()->endOfMonth()->min($to);

            $clicks = ReferralClick::where('referrer_user_id', $user->id)
                ->whereBetween('clicked_at', [$rangeStart, $rangeEnd])
                ->count();
            $signups = User::where('referred_by_user_id', $user->id)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->count();
            $orders = AffiliateCommission::where('referrer_user_id', $user->id)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->count();

            if (! $hideEmpty || ($clicks + $signups + $orders) > 0) {
                $rows[] = [
                    'month_key' => $cursor->format('Y-m'),
                    'date' => $cursor->format('M Y'),
                    'clicks' => $clicks,
                    'signups' => $signups,
                    'orders' => $orders,
                ];
            }

            $cursor->addMonth()->startOfMonth();
        }

        return array_reverse($rows);
    }

    protected function dailyBreakdownForUser(User $user, Carbon $from, Carbon $to, bool $hideEmpty): array
    {
        $rows = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();

            $clicks = ReferralClick::where('referrer_user_id', $user->id)
                ->whereBetween('clicked_at', [$dayStart, $dayEnd])
                ->count();
            $signups = User::where('referred_by_user_id', $user->id)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            $orders = AffiliateCommission::where('referrer_user_id', $user->id)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            if (! $hideEmpty || ($clicks + $signups + $orders) > 0) {
                $rows[] = [
                    'date' => $cursor->format('M d'),
                    'clicks' => $clicks,
                    'signups' => $signups,
                    'orders' => $orders,
                ];
            }

            $cursor->addDay();
        }

        return $rows;
    }

    protected function monthlyBreakdownAdmin(Carbon $from, Carbon $to, bool $hideEmpty): array
    {
        $rows = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $rangeStart = $cursor->copy()->max($from);
            $rangeEnd = $cursor->copy()->endOfMonth()->min($to);

            $clicks = ReferralClick::whereBetween('clicked_at', [$rangeStart, $rangeEnd])->count();
            $signups = User::whereNotNull('referred_by_user_id')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->count();
            $orders = AffiliateCommission::whereBetween('created_at', [$rangeStart, $rangeEnd])->count();

            if (! $hideEmpty || ($clicks + $signups + $orders) > 0) {
                $rows[] = [
                    'month_key' => $cursor->format('Y-m'),
                    'date' => $cursor->format('M Y'),
                    'clicks' => $clicks,
                    'signups' => $signups,
                    'orders' => $orders,
                ];
            }

            $cursor->addMonth()->startOfMonth();
        }

        return array_reverse($rows);
    }

    protected function dailyBreakdownAdmin(Carbon $from, Carbon $to, bool $hideEmpty): array
    {
        $rows = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();

            $clicks = ReferralClick::whereBetween('clicked_at', [$dayStart, $dayEnd])->count();
            $signups = User::whereNotNull('referred_by_user_id')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            $orders = AffiliateCommission::whereBetween('created_at', [$dayStart, $dayEnd])->count();

            if (! $hideEmpty || ($clicks + $signups + $orders) > 0) {
                $rows[] = [
                    'date' => $cursor->format('M d'),
                    'clicks' => $clicks,
                    'signups' => $signups,
                    'orders' => $orders,
                ];
            }

            $cursor->addDay();
        }

        return $rows;
    }

    protected function activityFeedForUser(User $user, int $limit): array
    {
        $events = collect();

        ReferralClick::where('referrer_user_id', $user->id)
            ->latest('clicked_at')
            ->limit($limit)
            ->get()
            ->each(fn (ReferralClick $c) => $events->push([
                'type' => 'click',
                'label' => 'Link click',
                'detail' => $c->landing_path ?: '/',
                'date' => $c->clicked_at->format('M d, Y H:i'),
                'ts' => $c->clicked_at->timestamp,
            ]));

        User::where('referred_by_user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->each(fn (User $u) => $events->push([
                'type' => 'signup',
                'label' => 'New signup',
                'detail' => $this->maskName($u->name),
                'date' => $u->created_at->format('M d, Y H:i'),
                'ts' => $u->created_at->timestamp,
            ]));

        AffiliateCommission::with(['referred', 'order'])
            ->where('referrer_user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->each(fn (AffiliateCommission $c) => $events->push([
                'type' => 'commission',
                'label' => 'Commission · '.ucfirst($c->status),
                'detail' => $this->formatMoney((float) $c->amount, $c->order?->currency ?? 'inr').' · '.$this->maskName($c->referred->name),
                'date' => $c->created_at->format('M d, Y H:i'),
                'ts' => $c->created_at->timestamp,
            ]));

        $referralIds = User::where('referred_by_user_id', $user->id)->pluck('id');

        Order::with('user')
            ->whereIn('user_id', $referralIds)
            ->where('status', 'refunded')
            ->latest()
            ->limit($limit)
            ->get()
            ->each(fn (Order $o) => $events->push([
                'type' => 'refund',
                'label' => 'Refund',
                'detail' => $o->order_number.' · '.$this->formatMoney((float) $o->total, $o->currency),
                'date' => $o->updated_at->format('M d, Y H:i'),
                'ts' => $o->updated_at->timestamp,
            ]));

        return $events->sortByDesc('ts')->take($limit)->values()->all();
    }

    protected function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        $first = $parts[0] ?? 'User';

        return $first.' '.(isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)).'.' : '');
    }
}
