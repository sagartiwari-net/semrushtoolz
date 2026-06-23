<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\Subscription;
use App\Models\ToolSession;
use App\Models\SiteSetting;
use App\Models\User;

class AdminDashboardService
{
    public function __construct(
        protected OrderService $orders,
        protected GoProxyClient $proxy,
        protected BuyahrefPaymentService $buyahref,
        protected PayPalService $paypal,
    ) {}

    public function stats(): array
    {
        $totalUsers = User::whereIn('role', ['user'])->count();
        $newThisWeek = User::whereIn('role', ['user'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $activeSubs = Subscription::where('status', 'active')
            ->where('ends_at', '>', now())
            ->count();

        $subRate = $totalUsers > 0
            ? round(($activeSubs / $totalUsers) * 100).'% of users'
            : 'No users yet';

        $monthStart = now()->startOfMonth();
        $monthRevenueInr = Order::where('status', 'completed')
            ->where('currency', 'inr')
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $monthRevenueUsd = Order::where('status', 'completed')
            ->where('currency', 'usd')
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthInr = Order::where('status', 'completed')
            ->where('currency', 'inr')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');

        $revenueTrend = $lastMonthInr > 0
            ? round((($monthRevenueInr - $lastMonthInr) / $lastMonthInr) * 100)
            : null;

        $revenueLabel = '₹'.number_format($monthRevenueInr, 0);
        if ($monthRevenueUsd > 0) {
            $revenueLabel .= ' + $'.number_format($monthRevenueUsd, 0);
        }

        $openTickets = SupportTicket::whereIn('status', [
            SupportTicket::STATUS_OPEN,
            SupportTicket::STATUS_REPLIED,
        ])->count();

        $highPriorityTickets = SupportTicket::where('priority', 'high')
            ->where('status', '!=', SupportTicket::STATUS_CLOSED)
            ->count();

        return [
            [
                'label' => 'Total Users',
                'value' => number_format($totalUsers),
                'sub' => $newThisWeek > 0 ? "+{$newThisWeek} this week" : 'No new users this week',
                'icon' => 'blue',
            ],
            [
                'label' => 'Active Subs',
                'value' => number_format($activeSubs),
                'sub' => $subRate,
                'icon' => 'green',
            ],
            [
                'label' => 'Revenue (Month)',
                'value' => $revenueLabel,
                'sub' => $revenueTrend !== null
                    ? ($revenueTrend >= 0 ? "+{$revenueTrend}%" : "{$revenueTrend}%").' vs last month (INR)'
                    : 'First month tracking',
                'icon' => 'orange',
            ],
            [
                'label' => 'Open Tickets',
                'value' => (string) $openTickets,
                'sub' => $highPriorityTickets > 0
                    ? "{$highPriorityTickets} high priority"
                    : ($openTickets > 0 ? 'Awaiting response' : 'All clear'),
                'icon' => 'purple',
            ],
        ];
    }

    public function recentOrders(int $limit = 10): array
    {
        return Order::with(['user', 'plan', 'tool'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->order_number,
                'order_id' => $o->id,
                'plan' => $o->purchasedItemName(),
                'amount' => $this->orders->formatAmount($o),
                'method' => ucfirst($o->payment_method ?? '—'),
                'status' => $this->orders->statusLabel($o->status),
                'status_raw' => $o->status,
                'date' => $o->created_at->format('M d, Y'),
            ])
            ->all();
    }

    public function pendingActions(): array
    {
        return [
            [
                'label' => 'Security alerts (account sharing)',
                'count' => \App\Models\SecurityAlert::where('status', 'open')->count(),
                'route' => 'admin.security',
            ],
            [
                'label' => 'UPI orders awaiting verify',
                'count' => Order::where('payment_method', 'upi')
                    ->whereIn('status', ['awaiting_payment', 'verifying'])
                    ->count(),
                'route' => 'admin.payments',
            ],
            [
                'label' => 'Offline payment proofs',
                'count' => Order::where('payment_method', 'offline')
                    ->whereIn('status', ['awaiting_proof', 'verifying'])
                    ->count(),
                'route' => 'admin.payments',
            ],
            [
                'label' => 'Affiliate commissions pending',
                'count' => AffiliateCommission::where('status', 'pending')->count(),
                'route' => 'admin.affiliates',
            ],
            [
                'label' => 'Affiliate payout requests',
                'count' => AffiliatePayout::where('status', AffiliatePayout::STATUS_PENDING)->count(),
                'route' => 'admin.affiliates',
            ],
            [
                'label' => 'Open support tickets',
                'count' => SupportTicket::where('status', SupportTicket::STATUS_OPEN)->count(),
                'route' => 'admin.tickets',
            ],
        ];
    }

    public function systemHealth(): array
    {
        $proxy = $this->proxy->health();
        $buyahrefOk = $this->buyahref->isConfigured();
        $paypalOk = $this->paypal->isConfigured();

        $liveSessions = ToolSession::query()
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->count();

        return [
            [
                'label' => 'Go Proxy API',
                'status' => $proxy['online'] ?? false ? 'Online' : ($proxy['status'] ?? 'Offline'),
                'ok' => $proxy['online'] ?? false,
            ],
            [
                'label' => 'UPI (Buyahref)',
                'status' => $buyahrefOk ? 'Configured' : 'Not configured',
                'ok' => $buyahrefOk,
            ],
            [
                'label' => 'PayPal',
                'status' => $paypalOk ? 'Connected ('.(SiteSetting::paypalConfig()['mode'] ?? 'sandbox').')' : 'Not configured',
                'ok' => $paypalOk,
            ],
            [
                'label' => 'Live Tool Sessions',
                'status' => (string) $liveSessions.' active',
                'ok' => true,
            ],
        ];
    }

    public function sessionStats(): array
    {
        $live = ToolSession::query()
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->count();

        $byTool = ToolSession::query()
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->selectRaw('tool, count(*) as total')
            ->groupBy('tool')
            ->pluck('total', 'tool');

        $seatCards = $byTool->take(2)->map(function ($count, $tool) {
            $label = ucfirst(str_replace('_', ' ', $tool));
            $seatInfo = app(ToolAccessService::class)->seatLabel($tool);

            return [
                'label' => "{$label} Seats",
                'value' => $seatInfo ?? (string) $count.' live',
                'icon' => 'orange',
            ];
        })->values()->all();

        while (count($seatCards) < 2) {
            $seatCards[] = [
                'label' => 'Tool Seats',
                'value' => '—',
                'icon' => 'green',
            ];
        }

        return [
            'live_sessions' => $live,
            'seat_cards' => $seatCards,
        ];
    }

    public function activeSessions(int $limit = 50): array
    {
        return ToolSession::with('user')
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->limit($limit)
            ->get()
            ->map(function (ToolSession $session) {
                $minutes = $session->started_at
                    ? (int) $session->started_at->diffInMinutes(now())
                    : 0;

                return [
                    'id' => $session->id,
                    'user' => $session->user?->name ?? '—',
                    'tool' => ucfirst(str_replace('_', ' ', $session->tool)),
                    'started' => $session->started_at?->format('g:i A') ?? '—',
                    'duration' => $minutes.' min',
                    'ip' => $session->ip_address ?? '—',
                ];
            })
            ->all();
    }
}
