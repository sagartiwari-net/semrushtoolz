<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Models\Tool;
use App\Services\DashboardPresenter;
use App\Services\GstService;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\ReferralSignupBonusService;
use App\Services\SubscriptionService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected DashboardPresenter $presenter,
        protected OrderService $orders,
        protected SubscriptionService $subscriptions,
        protected ReferralSignupBonusService $referralBonus,
        protected WalletService $wallet,
    ) {}

    protected function shared(): array
    {
        $user = Auth::user();

        return [
            'user' => $this->presenter->userContext($user),
            'notifications' => $this->presenter->notifications($user),
        ];
    }

    public function show(Request $request)
    {
        $request->validate([
            'plan' => ['nullable', 'string', 'exists:plans,slug', 'required_without:tool'],
            'tool' => ['nullable', 'string', 'exists:tools,slug', 'required_without:plan'],
            'duration_months' => ['nullable', 'integer', Rule::in(array_keys(config('pricing.durations')))],
            'duration_days' => ['nullable', 'integer', Rule::in(array_keys(config('pricing.trial_durations', [])))],
            'currency' => ['nullable', 'string', Rule::in(['inr', 'usd'])],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);

        $currency = $request->input('currency', 'inr');
        $activeSubs = $this->subscriptions->activeSubscriptions(Auth::user());
        $couponCode = strtoupper(trim((string) $request->input('coupon_code', '')));
        $user = Auth::user();
        $walletConfig = SiteSetting::walletConfig();

        if ($request->filled('tool')) {
            $tool = Tool::where('slug', $request->tool)->where('is_active', true)->where('show_in_shop', true)->firstOrFail();
            $durationMonths = (int) $request->input('duration_months', 1);
            $duration = config("pricing.durations.{$durationMonths}");
            $totals = $this->orders->calculateToolTotals($tool, $durationMonths, $currency);
            $checkoutData = $this->withCouponTotals($totals, $couponCode, $currency, $durationMonths, toolId: $tool->id);

            return view('dashboard.checkout', array_merge($this->shared(), $checkoutData, [
                'tool' => $tool,
                'plan' => null,
                'itemName' => $tool->name,
                'durationMonths' => $durationMonths,
                'durationDays' => null,
                'durationLabel' => $duration['label'] ?? "{$durationMonths} Month(s)",
                'currency' => $currency,
                'isTrial' => false,
                'paymentMethods' => $this->orders->availablePaymentMethods($currency, $user, (float) $checkoutData['totals']['total']),
                'walletBalance' => $this->wallet->balance($user),
                'walletEnabled' => $walletConfig['enabled'],
                'activeSubscriptions' => $activeSubs,
                'pricingConfig' => PricingService::jsConfig(),
                'activeNav' => 'dashboard.shop',
            ]));
        }

        $plan = Plan::where('slug', $request->plan)->where('is_active', true)->where('is_bundle', true)->firstOrFail();

        if ($plan->isTrial()) {
            $durationDays = (int) $request->input('duration_days', 1);
            $trialTier = config("pricing.trial_durations.{$durationDays}");
            $totals = $this->orders->finalizeTrialTotals(
                $this->orders->calculateTrialTotals($durationDays, $currency),
                $currency,
            );

            return view('dashboard.checkout', array_merge($this->shared(), [
                'totals' => $totals,
                'couponCode' => '',
                'appliedCoupon' => null,
                'couponError' => null,
                'referralBonusHint' => null,
                'gstLabel' => GstService::config()['label'],
            ], [
                'plan' => $plan,
                'tool' => null,
                'itemName' => $plan->name,
                'durationMonths' => null,
                'durationDays' => $durationDays,
                'durationLabel' => $trialTier['label'] ?? "{$durationDays} Day(s)",
                'currency' => $currency,
                'isTrial' => true,
                'paymentMethods' => $this->orders->availablePaymentMethods($currency, $user, (float) $totals['total'], true),
                'walletBalance' => $this->wallet->balance($user),
                'walletEnabled' => false,
                'activeSubscriptions' => $activeSubs,
                'pricingConfig' => PricingService::jsConfig(),
                'activeNav' => 'dashboard.shop',
            ]));
        }

        $durationMonths = (int) $request->input('duration_months', 1);
        $duration = config("pricing.durations.{$durationMonths}");
        $totals = $this->orders->calculateTotals($plan, $durationMonths, $currency);
        $checkoutData = $this->withCouponTotals($totals, $couponCode, $currency, $durationMonths, planId: $plan->id);

        return view('dashboard.checkout', array_merge($this->shared(), $checkoutData, [
            'plan' => $plan,
            'tool' => null,
            'itemName' => $plan->name,
            'durationMonths' => $durationMonths,
            'durationDays' => null,
            'durationLabel' => $duration['label'] ?? "{$durationMonths} Month(s)",
            'currency' => $currency,
            'isTrial' => false,
            'paymentMethods' => $this->orders->availablePaymentMethods($currency, $user, (float) $checkoutData['totals']['total']),
            'walletBalance' => $this->wallet->balance($user),
            'walletEnabled' => $walletConfig['enabled'],
            'activeSubscriptions' => $activeSubs,
            'pricingConfig' => PricingService::jsConfig(),
            'activeNav' => 'dashboard.shop',
        ]));
    }

    public function store(CheckoutRequest $request)
    {
        $couponCode = filled($request->coupon_code) ? strtoupper(trim($request->coupon_code)) : null;

        try {
            if ($request->filled('tool')) {
                $tool = Tool::where('slug', $request->tool)->where('is_active', true)->where('show_in_shop', true)->firstOrFail();
                $order = $this->orders->createToolOrder(
                    Auth::user(),
                    $tool,
                    (int) $request->duration_months,
                    $request->currency,
                    $request->payment_method,
                    $couponCode
                );
            } else {
                $plan = Plan::where('slug', $request->plan)->where('is_active', true)->where('is_bundle', true)->firstOrFail();

                if ($plan->isTrial()) {
                    $order = $this->orders->createTrialOrder(
                        Auth::user(),
                        $plan,
                        (int) $request->duration_days,
                        $request->currency,
                        $request->payment_method,
                    );
                } else {
                    $order = $this->orders->createOrder(
                        Auth::user(),
                        $plan,
                        (int) $request->duration_months,
                        $request->currency,
                        $request->payment_method,
                        $couponCode
                    );
                }
            }
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['payment_method' => $e->getMessage()]);
        }

        if ($order->payment_method === 'wallet' || $order->status === 'completed') {
            return redirect()->route('dashboard.orders.show', $order)
                ->with('success', 'Payment successful. Your access is now active.');
        }

        return redirect($this->orders->paymentRoute($order))
            ->with('success', 'Order created. Complete payment to activate your subscription.');
    }

    protected function withCouponTotals(
        array $totals,
        string $couponCode,
        string $currency,
        int $durationMonths,
        ?int $planId = null,
        ?int $toolId = null,
    ): array {
        $couponError = null;
        $appliedCoupon = null;

        if ($couponCode !== '') {
            try {
                $totals = $this->orders->finalizeCheckoutTotals(
                    $totals,
                    Auth::user(),
                    $currency,
                    $durationMonths,
                    $couponCode,
                    $planId,
                    $toolId,
                );
                $appliedCoupon = $totals['coupon'];
            } catch (ValidationException $e) {
                $couponError = $e->validator->errors()->first('coupon_code');
                $totals = $this->orders->finalizeCheckoutTotals(
                    $totals,
                    Auth::user(),
                    $currency,
                    $durationMonths,
                    null,
                    $planId,
                    $toolId,
                );
            }
        } else {
            $totals = $this->orders->finalizeCheckoutTotals(
                $totals,
                Auth::user(),
                $currency,
                $durationMonths,
                null,
                $planId,
                $toolId,
            );
        }

        return [
            'totals' => $totals,
            'couponCode' => $couponCode,
            'appliedCoupon' => $appliedCoupon,
            'couponError' => $couponError,
            'referralBonusHint' => $this->referralBonusHint($durationMonths),
            'gstLabel' => GstService::config()['label'],
        ];
    }

    protected function referralBonusHint(int $durationMonths): ?array
    {
        $user = Auth::user();

        if (! $this->referralBonus->isEnabled() || ! $user->referred_by_user_id) {
            return null;
        }

        if ($this->referralBonus->hasCompletedPurchase($user)) {
            return null;
        }

        if (! $user->referral_bonus_expires_at || $user->referral_bonus_expires_at->isPast()) {
            return null;
        }

        if ($durationMonths === 1) {
            return null;
        }

        return [
            'percent' => $this->referralBonus->percent(),
            'days_left' => $this->referralBonus->daysRemaining($user),
        ];
    }
}
