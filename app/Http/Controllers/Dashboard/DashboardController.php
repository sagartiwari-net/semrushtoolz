<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\AffiliateWalletTransferRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\ActivityFeedService;
use App\Services\AffiliateExportService;
use App\Services\AffiliateService;
use App\Services\AffiliateWalletTransferService;
use App\Support\AffiliateReportFilters;
use App\Support\TablePageSize;
use App\Services\DashboardPresenter;
use App\Services\ExtensionAccessService;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\SubscriptionService;
use App\Services\SupportTicketService;
use App\Services\WalletService;
use App\Services\ToolAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardPresenter $presenter,
        protected SubscriptionService $subscriptions,
        protected ToolAccessService $toolAccess,
        protected AffiliateService $affiliates,
        protected ExtensionAccessService $extensionAccess,
        protected ActivityFeedService $activityFeed,
        protected SupportTicketService $supportTickets,
    ) {}

    protected function shared(): array
    {
        $user = Auth::user();

        return [
            'user' => $this->presenter->userContext($user),
            'notifications' => $this->presenter->notifications($user),
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $topTool = $this->activityFeed->topToolForUser($user);
        $walletConfig = SiteSetting::walletConfig();
        $walletService = app(WalletService::class);

        return view('dashboard.index', array_merge($this->shared(), [
            'tools' => $this->subscriptions->accessibleTools($user),
            'activity' => $this->activityFeed->forUser($user),
            'activityTotal' => $this->activityFeed->totalForUser($user),
            'topTool' => $topTool,
            'walletEnabled' => $walletConfig['enabled'],
            'walletBalanceLabel' => $walletService->formatInr($walletService->balance($user)),
            'activeNav' => 'dashboard.index',
        ]));
    }

    public function activity()
    {
        $user = Auth::user();
        $perPage = TablePageSize::resolve(request(), 10);

        return view('dashboard.activity', array_merge($this->shared(), [
            'activity' => $this->activityFeed->paginatedForUser($user, $perPage),
            'perPage' => $perPage,
            'activeNav' => 'dashboard.index',
        ]));
    }

    public function shop()
    {
        $user = Auth::user();
        $walletConfig = SiteSetting::walletConfig();
        $walletService = app(WalletService::class);

        return view('dashboard.shop', array_merge($this->shared(), [
            'toolGroups' => PricingService::shopToolsGrouped(),
            'bundlePlans' => PricingService::bundlePlans(),
            'walletEnabled' => $walletConfig['enabled'],
            'walletBalanceLabel' => $walletService->formatInr($walletService->balance($user)),
            'activeNav' => 'dashboard.shop',
        ]));
    }

    public function tools()
    {
        $user = Auth::user();
        $tools = collect($this->subscriptions->accessibleTools($user))->map(function ($tool) use ($user) {
            $session = $this->toolAccess->activeSession($user, $tool['id']);

            return array_merge($tool, [
                'seats' => $this->toolAccess->seatLabel($tool['id']) ?? '—',
                'last_accessed' => $this->toolAccess->lastAccessed($user, $tool['id']),
                'session' => $session ? [
                    'minutes_remaining' => $session->minutesRemaining(),
                    'access_url' => $session->access_url,
                ] : null,
            ]);
        })->all();

        return view('dashboard.tools', array_merge($this->shared(), [
            'tools' => $tools,
            'activeNav' => 'dashboard.tools',
        ]));
    }

    public function orders()
    {
        $orderService = app(OrderService::class);
        $orders = Auth::user()->orders()
            ->with(['plan', 'tool'])
            ->latest()
            ->get()
            ->map(fn ($o) => [
                'id' => $o->order_number,
                'order_id' => $o->id,
                'plan' => $o->purchasedItemName(),
                'amount' => ($o->currency === 'inr' ? '₹' : '$').number_format($o->total, $o->currency === 'usd' && $o->total < 100 ? 2 : 0),
                'method' => $orderService->paymentMethodLabel($o),
                'status' => $orderService->statusLabel($o->status),
                'status_raw' => $o->status,
                'date' => $o->created_at->format('M d, Y'),
            ]);

        return view('dashboard.orders', array_merge($this->shared(), [
            'orders' => $orders,
            'activeNav' => 'dashboard.orders',
        ]));
    }

    public function extensions()
    {
        $user = Auth::user();

        return view('dashboard.extensions', array_merge($this->shared(), [
            'activeNav' => 'dashboard.extensions',
            'config' => SiteSetting::extensionConfig(),
            'canDownload' => $this->extensionAccess->userCanDownloadExtension($user),
            'extensionTools' => $this->extensionAccess->extensionToolsForUser($user),
        ]));
    }

    public function affiliates()
    {
        $user = Auth::user();
        $stats = $this->affiliates->statsFor($user);
        $walletConfig = SiteSetting::walletConfig();
        $perPage = TablePageSize::resolve(request());
        $activeTab = request()->query('tab', 'commissions');
        $reportFilters = AffiliateReportFilters::fromRequest(request());
        $report = $this->affiliates->reportForUser($user, $reportFilters);

        return view('dashboard.affiliates', array_merge($this->shared(), [
            'stats' => $stats,
            'walletConfig' => $walletConfig,
            'commissions' => $this->affiliates->paginatedCommissionsForUser($user, $perPage),
            'payoutHistory' => $this->affiliates->paginatedPayoutHistory($user, $perPage),
            'activity' => $this->affiliates->paginatedActivity($user, $perPage),
            'report' => $report,
            'reportFilters' => $reportFilters,
            'reportBreakdown' => $this->affiliates->paginatedReportBreakdown($report['breakdown'], $perPage),
            'perPage' => $perPage,
            'activeTab' => $activeTab,
            'activeNav' => 'dashboard.affiliates',
        ]));
    }

    public function exportAffiliates(string $format)
    {
        abort_unless(in_array($format, ['csv', 'xlsx', 'excel'], true), 404);

        return app(AffiliateExportService::class)->download(Auth::user(), $format);
    }

    public function support()
    {
        $user = Auth::user();

        return view('dashboard.support', array_merge($this->shared(), [
            'tickets' => $this->supportTickets->listForUser($user),
            'contact' => SiteSetting::supportConfig(),
            'activeNav' => 'dashboard.support',
        ]));
    }

    public function profile()
    {
        return view('dashboard.profile', array_merge($this->shared(), [
            'activeNav' => 'dashboard.profile',
        ]));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $emailChanged = $user->email !== $data['email'];

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();

            return back()->with('success', 'Profile updated. Please check your inbox to verify your new email address.');
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        $user->update(['avatar_path' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    public function removeAvatar()
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function requestPayout(Request $request)
    {
        $data = $request->validate([
            'method' => ['required', 'in:upi,bank'],
            'payout_detail' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->affiliates->requestPayout($request->user(), $data['method'], $data['payout_detail']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payout request submitted. We will process it within 2–3 business days.');
    }

    public function transferToWallet(AffiliateWalletTransferRequest $request, AffiliateWalletTransferService $transferService)
    {
        try {
            $result = $transferService->transfer($request->user(), (float) $request->amount);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            '₹'.number_format($result['total_credited'], 0).' credited to your wallet (₹'.number_format($result['principal'], 0).' + ₹'.number_format($result['bonus'], 0).' bonus). This transfer cannot be reversed.'
        );
    }

    public function settings()
    {
        $user = Auth::user();

        return view('dashboard.settings', array_merge($this->shared(), [
            'preferences' => $user->resolvedPreferences(),
            'loginLogs' => $user->loginLogs()->latest('logged_at')->limit(10)->get(),
            'activeNav' => 'dashboard.settings',
        ]));
    }
}
