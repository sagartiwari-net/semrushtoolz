<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Plan;
use App\Models\ResellerLedger;
use App\Models\SiteSetting;
use App\Models\Tool;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PayPalService;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected AffiliateService $affiliates,
        protected CouponService $coupons,
        protected ReferralSignupBonusService $referralBonus,
        protected TransactionalEmailService $transactionalMail,
        protected WalletService $wallet,
        protected WalletCashbackService $walletCashback,
        protected ResellerBalanceService $resellerBalances,
    ) {}

    public function calculateTotals(Plan $plan, int $durationMonths, string $currency): array
    {
        $monthly = $currency === 'inr' ? $plan->price_inr : $plan->price_usd;

        return $this->calculateFromMonthly((float) $monthly, $durationMonths);
    }

    public function calculateToolTotals(Tool $tool, int $durationMonths, string $currency): array
    {
        $monthly = $currency === 'inr' ? $tool->price_inr : $tool->price_usd;

        return $this->calculateFromMonthly((float) $monthly, $durationMonths);
    }

    protected function calculateFromMonthly(float $monthly, int $durationMonths): array
    {
        $durations = config('pricing.durations');
        $discount = $durations[$durationMonths]['discount'] ?? 0;
        $pricing = PricingService::calculatePrice($monthly, $durationMonths, $discount);

        return [
            'subtotal' => $pricing['subtotal'],
            'discount' => $pricing['saved'],
            'duration_discount' => $pricing['saved'],
            'total' => $pricing['total'],
            'discount_percent' => $pricing['discount_percent'],
            'per_month' => $pricing['per_month'],
            'coupon' => null,
            'coupon_code' => null,
            'coupon_discount' => 0.0,
            'referral_bonus_discount' => 0.0,
            'referral_bonus_percent' => null,
        ];
    }

    public function resolveCouponForOrder(
        ?string $couponCode,
        array $totals,
        string $currency,
        User $user,
        ?int $planId = null,
        ?int $toolId = null,
        int $durationMonths = 1,
    ): array {
        if (! filled($couponCode)) {
            return $totals;
        }

        $coupon = $this->coupons->findValidCode($couponCode);
        if (! $coupon) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'coupon_code' => 'Invalid or expired coupon code.',
            ]);
        }

        $this->coupons->validateForCheckout($coupon, $user, $totals, $currency, $planId, $toolId, $durationMonths);

        return $this->coupons->applyToTotals($totals, $coupon, $currency);
    }

    public function finalizeCheckoutTotals(
        array $totals,
        User $user,
        string $currency,
        int $durationMonths,
        ?string $couponCode = null,
        ?int $planId = null,
        ?int $toolId = null,
    ): array {
        $totals = $this->referralBonus->applyToTotals($totals, $user, $currency, $durationMonths);

        if (filled($couponCode)) {
            $totals = $this->resolveCouponForOrder(
                $couponCode,
                $totals,
                $currency,
                $user,
                $planId,
                $toolId,
                $durationMonths,
            );
        }

        return GstService::applyToTotals($totals, $currency, $durationMonths);
    }

    public function createOrder(
        User $user,
        Plan $plan,
        int $durationMonths,
        string $currency,
        string $paymentMethod,
        ?string $couponCode = null,
    ): Order {
        if ($paymentMethod === 'paypal') {
            $currency = 'usd';
        }

        if ($paymentMethod === 'wallet' && $currency !== 'inr') {
            throw new \RuntimeException('Wallet payments are only available in INR.');
        }

        $totals = $this->finalizeCheckoutTotals(
            $this->calculateTotals($plan, $durationMonths, $currency),
            $user,
            $currency,
            $durationMonths,
            $couponCode,
            planId: $plan->id,
        );

        return $this->finalizeNewOrder(
            $this->storeOrder($user, $totals, $durationMonths, $currency, $paymentMethod, planId: $plan->id),
            $paymentMethod,
        );
    }

    public function createToolOrder(
        User $user,
        Tool $tool,
        int $durationMonths,
        string $currency,
        string $paymentMethod,
        ?string $couponCode = null,
    ): Order {
        if ($paymentMethod === 'paypal') {
            $currency = 'usd';
        }

        if ($paymentMethod === 'wallet' && $currency !== 'inr') {
            throw new \RuntimeException('Wallet payments are only available in INR.');
        }

        $totals = $this->finalizeCheckoutTotals(
            $this->calculateToolTotals($tool, $durationMonths, $currency),
            $user,
            $currency,
            $durationMonths,
            $couponCode,
            toolId: $tool->id,
        );

        return $this->finalizeNewOrder(
            $this->storeOrder($user, $totals, $durationMonths, $currency, $paymentMethod, toolId: $tool->id),
            $paymentMethod,
        );
    }

    public function createTrialOrder(
        User $user,
        Plan $plan,
        int $durationDays,
        string $currency,
        string $paymentMethod,
    ): Order {
        if (! $plan->isTrial()) {
            throw new \InvalidArgumentException('Not a trial plan.');
        }

        if (in_array($paymentMethod, ['wallet', 'paypal'], true)) {
            throw new \RuntimeException('Trial plans cannot be paid with wallet or PayPal.');
        }

        if ($paymentMethod === 'wallet' && $currency !== 'inr') {
            throw new \RuntimeException('Wallet payments are only available in INR.');
        }

        $totals = $this->finalizeTrialTotals(
            $this->calculateTrialTotals($durationDays, $currency),
            $currency,
        );

        return $this->finalizeNewOrder(
            $this->storeOrder(
                $user,
                $totals,
                0,
                $currency,
                $paymentMethod,
                planId: $plan->id,
                durationDays: $durationDays,
            ),
            $paymentMethod,
        );
    }

    public function calculateTrialTotals(int $durationDays, string $currency): array
    {
        $tier = config("pricing.trial_durations.{$durationDays}");

        if (! $tier) {
            throw new \InvalidArgumentException('Invalid trial duration.');
        }

        $price = (float) ($currency === 'inr' ? $tier['price_inr'] : $tier['price_usd']);

        return [
            'subtotal' => $price,
            'discount' => 0,
            'duration_discount' => 0,
            'total' => $price,
            'discount_percent' => 0,
            'per_month' => $price,
            'coupon' => null,
            'coupon_code' => null,
            'coupon_discount' => 0.0,
            'referral_bonus_discount' => 0.0,
            'referral_bonus_percent' => null,
        ];
    }

    public function finalizeTrialTotals(array $totals, string $currency): array
    {
        return GstService::applyToTotals($totals, $currency, 1);
    }

    public function createTopupOrder(User $user, int $amount, string $paymentMethod): Order
    {
        if (! in_array($amount, SiteSetting::walletConfig()['topup_amounts'], true)) {
            throw new \InvalidArgumentException('Invalid top-up amount.');
        }

        $totals = GstService::applyToTotals([
            'subtotal' => (float) $amount,
            'discount' => 0,
            'duration_discount' => 0,
            'total' => (float) $amount,
            'discount_percent' => 0,
            'per_month' => (float) $amount,
            'coupon' => null,
            'coupon_code' => null,
            'coupon_discount' => 0.0,
            'referral_bonus_discount' => 0.0,
            'referral_bonus_percent' => null,
        ], 'inr', 1);

        return $this->storeOrder(
            $user,
            $totals,
            1,
            'inr',
            $paymentMethod,
            orderType: 'wallet_topup',
        );
    }

    /**
     * Reseller prepaid balance top-up via UPI (INR). Cash / manual via admin request.
     */
    public function createResellerBalanceTopupOrder(User $reseller, float $creditInr, string $paymentMethod): Order
    {
        if (! $reseller->isReseller()) {
            throw new \InvalidArgumentException('Only reseller accounts can create balance top-ups.');
        }

        $creditInr = round($creditInr, 2);
        if ($creditInr < 1) {
            throw new \InvalidArgumentException('Minimum top-up is ₹1.');
        }

        if ($paymentMethod !== 'upi') {
            throw new \InvalidArgumentException('Reseller balance top-up supports UPI only. Use admin request for cash / manual credit.');
        }

        $totals = [
            'subtotal' => $creditInr,
            'discount' => 0,
            'duration_discount' => 0,
            'total' => $creditInr,
            'discount_percent' => 0,
            'per_month' => $creditInr,
            'coupon' => null,
            'coupon_code' => null,
            'coupon_discount' => 0.0,
            'referral_bonus_discount' => 0.0,
            'referral_bonus_percent' => null,
            'taxable_amount' => $creditInr,
            'gst_rate' => null,
            'gst_amount' => 0,
        ];

        $order = $this->storeOrder(
            $reseller,
            $totals,
            1,
            'inr',
            'upi',
            orderType: 'reseller_balance_topup',
        );

        $order->update(['balance_credit_inr' => $creditInr]);

        return $order->fresh();
    }

    protected function finalizeNewOrder(Order $order, string $paymentMethod): Order
    {
        if ($paymentMethod !== 'wallet') {
            return $order;
        }

        return $this->payOrderWithWallet($order);
    }

    public function payOrderWithWallet(Order $order): Order
    {
        if ($order->payment_method !== 'wallet') {
            throw new \RuntimeException('Order is not a wallet payment.');
        }

        if ($order->currency !== 'inr') {
            throw new \RuntimeException('Wallet payments are INR only.');
        }

        $order->loadMissing('user');

        if ($this->wallet->balance($order->user) < (float) $order->total) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        $this->wallet->debit(
            $order->user,
            (float) $order->total,
            WalletTransaction::TYPE_PURCHASE,
            $order->isWalletTopup() ? 'Wallet top-up' : 'Subscription: '.$order->purchasedItemName(),
            $order,
        );

        $order->update(['wallet_amount_used' => $order->total]);

        return $this->completeOrder($order);
    }

    protected function storeOrder(
        User $user,
        array $totals,
        int $durationMonths,
        string $currency,
        string $paymentMethod,
        ?int $planId = null,
        ?int $toolId = null,
        string $orderType = 'subscription',
        ?int $durationDays = null,
    ): Order {
        $expiryMinutes = match ($paymentMethod) {
            'upi' => SiteSetting::buyahrefConfig()['order_expiry_minutes'],
            'paypal' => config('payments.order_expiry_minutes', 30),
            default => config('payments.order_expiry_minutes', 30),
        };

        $status = match ($paymentMethod) {
            'wallet' => 'pending',
            'upi', 'paypal' => 'awaiting_payment',
            'offline' => 'awaiting_proof',
            default => 'pending',
        };

        if ($paymentMethod === 'paypal') {
            $currency = 'usd';
        }

        return tap(Order::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'tool_id' => $toolId,
            'order_number' => Order::generateOrderNumber(),
            'order_type' => $orderType,
            'duration_months' => $durationMonths,
            'duration_days' => $durationDays,
            'currency' => $currency,
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'coupon_id' => $totals['coupon']?->id,
            'coupon_code' => $totals['coupon_code'] ?? null,
            'coupon_discount' => $totals['coupon_discount'] ?? 0,
            'referral_bonus_discount' => $totals['referral_bonus_discount'] ?? 0,
            'referral_bonus_percent' => $totals['referral_bonus_percent'] ?? null,
            'taxable_amount' => $totals['taxable_amount'] ?? $totals['total'],
            'gst_rate' => $totals['gst_rate'] ?? null,
            'gst_amount' => $totals['gst_amount'] ?? 0,
            'total' => $totals['total'],
            'payment_method' => $paymentMethod,
            'is_recurring' => $paymentMethod === 'paypal' && $durationDays === null,
            'status' => $status,
            'expires_at' => in_array($paymentMethod, ['upi', 'paypal'], true) ? now()->addMinutes($expiryMinutes) : null,
        ]), function (Order $order) use ($paymentMethod) {
            if ($paymentMethod !== 'wallet') {
                $this->transactionalMail->sendSubscriptionPending($order);
            }
        });
    }

    public function completeOrder(Order $order, ?string $adminNote = null): Order
    {
        return DB::transaction(function () use ($order, $adminNote) {
            $order->refresh();

            if ($order->status === 'completed') {
                return $order;
            }

            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'admin_note' => $adminNote ?? $order->admin_note,
            ]);

            if ($order->isWalletTopup()) {
                $order->loadMissing('user');
                $this->wallet->credit(
                    $order->user,
                    (float) $order->total,
                    WalletTransaction::TYPE_TOPUP,
                    'Wallet top-up via '.$order->payment_method,
                    $order,
                );

                $order = $order->fresh();
                $this->transactionalMail->sendWalletTopupCompleted($order);

                return $order;
            }

            if ($order->isResellerBalanceTopup()) {
                $order->loadMissing('user');
                $credit = $order->creditAmountInr();
                if ($credit <= 0) {
                    throw new \RuntimeException('Invalid reseller balance credit amount.');
                }

                $this->resellerBalances->credit(
                    $order->user,
                    $credit,
                    ResellerLedger::TYPE_CREDIT_PAYMENT,
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'payment_method' => $order->payment_method,
                        'charged_total' => (float) $order->total,
                        'charged_currency' => $order->currency,
                    ],
                );

                return $order->fresh();
            }

            $subscription = $this->subscriptions->activateFromOrder($order);
            $this->affiliates->createCommissionForOrder($order);

            if ($order->coupon_id) {
                $this->coupons->recordUse($order->coupon);
            }

            $this->walletCashback->maybeAward($order->fresh());

            $this->transactionalMail->sendSubscriptionPaid($order, $subscription);

            return $order->fresh();
        });
    }

    public function rejectOrder(Order $order, string $reason): Order
    {
        $order->update([
            'status' => 'rejected',
            'admin_note' => $reason,
        ]);

        return $order->fresh();
    }

    public function cancelOrder(Order $order): Order
    {
        if ($order->status === 'completed') {
            return $order;
        }

        $order->update(['status' => 'cancelled']);

        return $order->fresh();
    }

    public function paymentRoute(Order $order): string
    {
        if ($order->isResellerBalanceTopup()) {
            if ($order->payment_method === 'upi') {
                return route('reseller.balance.pay.upi', $order);
            }

            return route('reseller.balance.pay.paypal', $order);
        }

        if ($order->payment_method === 'wallet' || $order->status === 'completed') {
            return route('dashboard.orders.show', $order);
        }

        return match ($order->payment_method) {
            'upi' => route('dashboard.orders.pay.upi', $order),
            'offline' => route('dashboard.orders.pay.offline', $order),
            default => route('dashboard.orders.pay.paypal', $order),
        };
    }

    public function paymentMethodLabel(Order $order): string
    {
        if ($order->payment_method === 'wallet') {
            return 'SemrushToolz Wallet';
        }

        return ucfirst($order->payment_method ?? '—');
    }

    public function formatAmount(Order $order): string
    {
        $symbol = $order->currency === 'inr' ? '₹' : '$';
        $decimals = $order->currency === 'usd' && $order->total < 100 ? 2 : 0;

        return $symbol.number_format($order->total, $decimals);
    }

    public function refundOrder(Order $order, string $reason): Order
    {
        if ($order->status !== 'completed') {
            throw new \RuntimeException('Only completed orders can be refunded.');
        }

        return DB::transaction(function () use ($order, $reason) {
            if ($order->payment_method === 'wallet' && (float) $order->wallet_amount_used > 0) {
                $order->loadMissing('user');
                $this->wallet->credit(
                    $order->user,
                    (float) $order->wallet_amount_used,
                    WalletTransaction::TYPE_REFUND,
                    'Refund for order '.$order->order_number,
                    $order,
                );
            }

            $this->walletCashback->reverseForOrder($order);

            $subscription = $this->subscriptions->findActiveForOrder($order);
            if ($subscription) {
                $this->subscriptions->cancelWithoutRefund($subscription);
            }

            $order->update([
                'status' => 'refunded',
                'admin_note' => $reason,
            ]);

            $this->affiliates->reverseCommissionForOrder($order);

            return $order->fresh();
        });
    }

    public function revokeSubscriptionForOrder(Order $order, ?string $note = null): Order
    {
        if ($order->status !== 'completed') {
            throw new \RuntimeException('Only completed orders can have access revoked.');
        }

        if ($order->isWalletTopup() || $order->isResellerBalanceTopup()) {
            throw new \RuntimeException('Balance top-up orders do not have a subscription.');
        }

        $subscription = $this->subscriptions->findActiveForOrder($order);

        if (! $subscription) {
            throw new \RuntimeException('No active subscription found for this order.');
        }

        return DB::transaction(function () use ($order, $subscription, $note) {
            $this->subscriptions->cancelWithoutRefund($subscription);

            if ($note) {
                $existing = trim((string) $order->admin_note);
                $order->update([
                    'admin_note' => $existing !== '' ? $existing."\n".$note : $note,
                ]);
            }

            return $order->fresh();
        });
    }

    public function markRefundedFromPayPal(Order $order, string $note): Order
    {
        if ($order->status === 'refunded') {
            return $order;
        }

        if ($order->status !== 'completed') {
            throw new \RuntimeException('Only completed orders can be marked refunded from PayPal.');
        }

        return DB::transaction(function () use ($order, $note) {
            $subscription = $order->subscription_id
                ? $order->subscription
                : $this->subscriptions->findActiveForOrder($order);

            if ($subscription && $subscription->isActive()) {
                $this->subscriptions->cancelWithoutRefund($subscription, 'PayPal refund');
            }

            $this->walletCashback->reverseForOrder($order);

            $existing = trim((string) $order->admin_note);
            $order->update([
                'status' => 'refunded',
                'admin_note' => $existing !== '' ? $existing."\n".$note : $note,
            ]);

            $this->affiliates->reverseCommissionForOrder($order);

            return $order->fresh();
        });
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'awaiting_payment' => 'Awaiting Payment',
            'awaiting_proof' => 'Awaiting Proof',
            'verifying' => 'Verifying',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($status),
        };
    }

    public function availablePaymentMethods(string $currency, ?User $user = null, ?float $orderTotal = null, bool $isTrial = false): array
    {
        $methods = config("pricing.payment_methods.{$currency}", []);

        if ($isTrial) {
            $methods = array_values(array_filter($methods, fn ($m) => ! in_array($m['id'], ['paypal'], true)));

            return $methods;
        }

        if ($currency === 'inr' && ! app(BuyahrefPaymentService::class)->isConfigured()) {
            $methods = array_values(array_filter($methods, fn ($m) => $m['id'] !== 'upi'));
        } elseif ($currency === 'inr') {
            $bh = SiteSetting::buyahrefConfig();
            $methods = array_map(function ($m) use ($bh) {
                if ($m['id'] === 'upi') {
                    $m['name'] = $bh['display_name'];
                    if ($bh['display_description'] !== '') {
                        $m['desc'] = $bh['display_description'];
                    }
                }

                return $m;
            }, $methods);
        }

        if ($currency === 'usd' && ! app(PayPalService::class)->isConfigured()) {
            $methods = array_values(array_filter($methods, fn ($m) => $m['id'] !== 'paypal'));
        }

        if ($currency === 'inr' && $user && $this->wallet->isEnabled()) {
            $balance = $this->wallet->balance($user);
            if ($orderTotal !== null && $balance >= $orderTotal) {
                array_unshift($methods, [
                    'id' => 'wallet',
                    'name' => 'SemrushToolz Wallet',
                    'desc' => 'Pay instantly from your wallet (₹'.number_format($balance, 0).')',
                ]);
            }
        }

        return $methods;
    }
}
