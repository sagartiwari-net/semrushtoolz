<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\AdminUserQueryService;
use App\Services\OrderExportService;
use App\Services\OrderService;
use App\Support\TablePageSize;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboard,
        protected AdminUserQueryService $userQueries,
    ) {}

    public function index()
    {
        return view('admin.index', [
            'stats' => $this->dashboard->stats(),
            'recentOrders' => $this->dashboard->recentOrders(),
            'pendingActions' => $this->dashboard->pendingActions(),
            'systemHealth' => $this->dashboard->systemHealth(),
        ]);
    }

    public function users(Request $request)
    {
        $perPage = TablePageSize::resolve($request);

        $query = User::with(['subscriptions.plan', 'subscriptions.tool'])
            ->whereIn('role', ['user', 'admin', 'super_admin'])
            ->whereNotNull('email_verified_at');

        $this->userQueries->applyFilters($query, $request);

        $users = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($u) => $this->userQueries->presentUser($u));

        $filterOptions = $this->userQueries->filterOptions();
        $unverifiedStats = $this->userQueries->unverifiedStats();

        return view('admin.users', [
            'users' => $users,
            'perPage' => $perPage,
            'plans' => $filterOptions['plans'],
            'tools' => $filterOptions['tools'],
            'unverifiedStats' => $unverifiedStats,
            'filters' => [
                'q' => $request->q,
                'status' => $request->status ?? 'all',
                'plan_id' => $request->plan_id,
                'tool_id' => $request->tool_id,
                'subscription' => $request->input('subscription', 'all'),
            ],
        ]);
    }

    public function unverifiedUsers(Request $request)
    {
        $perPage = TablePageSize::resolve($request);

        $query = User::with(['subscriptions', 'orders'])
            ->where('role', 'user')
            ->whereNull('email_verified_at');

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($u) => $this->userQueries->presentUnverifiedUser($u));

        $unverifiedStats = $this->userQueries->unverifiedStats();

        return view('admin.users-unverified', [
            'users' => $users,
            'perPage' => $perPage,
            'unverifiedStats' => $unverifiedStats,
            'filters' => [
                'q' => $request->q,
            ],
        ]);
    }

    public function orders(Request $request)
    {
        $perPage = TablePageSize::resolve($request);
        $orderService = app(OrderService::class);

        $query = Order::with(['user', 'plan', 'tool'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('method') && $request->method !== 'all') {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate($perPage)
            ->withQueryString()
            ->through(fn ($o) => [
                'id' => $o->order_number,
                'order_id' => $o->id,
                'user' => $o->user->name,
                'plan' => $o->purchasedItemName(),
                'amount' => $orderService->formatAmount($o),
                'method' => $orderService->paymentMethodLabel($o),
                'status' => $orderService->statusLabel($o->status),
                'status_raw' => $o->status,
                'date' => $o->created_at->format('M d, Y'),
                'can_invoice' => in_array($o->status, ['completed', 'refunded'], true),
            ]);

        return view('admin.orders', [
            'orders' => $orders,
            'perPage' => $perPage,
            'filters' => [
                'q' => $request->q,
                'status' => $request->status ?? 'all',
                'method' => $request->method ?? 'all',
            ],
        ]);
    }

    public function exportOrders(Request $request, OrderExportService $export)
    {
        return $export->download($request);
    }

    public function payments()
    {
        $orderService = app(OrderService::class);

        $pendingUpi = Order::with('user')
            ->where('payment_method', 'upi')
            ->whereIn('status', ['awaiting_payment', 'verifying'])
            ->latest()
            ->limit(20)
            ->get();

        $pendingOffline = Order::with('user')
            ->where('payment_method', 'offline')
            ->whereIn('status', ['awaiting_proof', 'verifying'])
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.payments', [
            'pendingUpi' => $pendingUpi,
            'pendingOffline' => $pendingOffline,
            'orderService' => $orderService,
            'proxyHealth' => app(\App\Services\GoProxyClient::class)->health(),
        ]);
    }
}
