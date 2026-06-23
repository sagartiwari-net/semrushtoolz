<?php

namespace App\Services;

use App\Models\EmailNotificationLog;
use App\Models\EmailPreset;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Tool;
use App\Models\User;
use App\Services\MailPanel\MailPanelService;
use Illuminate\Support\Facades\Log;

class TransactionalEmailService
{
    public function __construct(
        protected MailPanelService $mailPanel,
    ) {}

    public function sendSubscriptionPaid(Order $order, Subscription $subscription): void
    {
        $order->loadMissing(['user', 'plan', 'tool']);

        if (! $order->user) {
            return;
        }

        $this->sendOnce(
            user: $order->user,
            presetKey: EmailPreset::KEY_SUBSCRIPTION_PAID,
            referenceType: 'order',
            referenceId: $order->id,
            data: [
                'name' => $order->user->name,
                'plan_name' => $order->purchasedItemName(),
                'amount' => $this->formatAmount($order->total, $order->currency),
                'currency' => strtoupper($order->currency),
                'order_id' => $order->order_number,
                'expires_at' => $subscription->ends_at->format('d M Y'),
                'dashboard_url' => route('dashboard.tools'),
            ],
        );
    }

    public function sendSubscriptionPending(Order $order): void
    {
        if (! in_array($order->status, ['awaiting_payment', 'pending', 'awaiting_proof'], true)) {
            return;
        }

        $order->loadMissing(['user', 'plan', 'tool']);

        if (! $order->user) {
            return;
        }

        $this->sendOnce(
            user: $order->user,
            presetKey: EmailPreset::KEY_SUBSCRIPTION_PENDING,
            referenceType: 'order',
            referenceId: $order->id,
            data: [
                'name' => $order->user->name,
                'plan_name' => $order->purchasedItemName(),
                'amount' => $this->formatAmount($order->total, $order->currency),
                'currency' => strtoupper($order->currency),
                'checkout_url' => url($this->paymentUrl($order)),
                'expires_at' => $order->expires_at?->format('d M Y, h:i A') ?? 'soon',
            ],
        );
    }

    public function sendWalletTopupCompleted(Order $order): void
    {
        $order->loadMissing('user');

        if (! $order->user || ! $order->isWalletTopup()) {
            return;
        }

        $balance = app(WalletService::class)->balance($order->user);

        $this->sendOnce(
            user: $order->user,
            presetKey: EmailPreset::KEY_WALLET_TOPUP_COMPLETED,
            referenceType: 'wallet_topup',
            referenceId: $order->id,
            data: [
                'name' => $order->user->name,
                'amount' => $this->formatAmount($order->total, 'inr'),
                'currency' => 'INR',
                'order_id' => $order->order_number,
                'wallet_balance' => $this->formatAmount($balance, 'inr'),
                'wallet_url' => route('dashboard.wallet'),
            ],
        );
    }

    public function sendWalletCashback(Order $order, float $cashbackAmount): void
    {
        $order->loadMissing('user');

        if (! $order->user || $cashbackAmount <= 0) {
            return;
        }

        $balance = app(WalletService::class)->balance($order->user);

        $this->sendOnce(
            user: $order->user,
            presetKey: EmailPreset::KEY_WALLET_CASHBACK_RECEIVED,
            referenceType: 'wallet_cashback',
            referenceId: $order->id,
            data: [
                'name' => $order->user->name,
                'cashback_amount' => $this->formatAmount($cashbackAmount, 'inr'),
                'purchase_name' => $order->purchasedItemName(),
                'order_id' => $order->order_number,
                'wallet_balance' => $this->formatAmount($balance, 'inr'),
                'wallet_url' => route('dashboard.wallet'),
            ],
        );
    }

    public function sendPlanExpiryReminder(Subscription $subscription, string $presetKey): void
    {
        $subscription->loadMissing(['user', 'plan', 'tool']);

        if (! $subscription->user || ! $subscription->ends_at) {
            return;
        }

        $planName = $subscription->plan?->name ?? $subscription->tool?->name ?? 'Your plan';

        $this->sendOnce(
            user: $subscription->user,
            presetKey: $presetKey,
            referenceType: 'subscription',
            referenceId: $subscription->id,
            data: [
                'name' => $subscription->user->name,
                'plan_name' => $planName,
                'expires_at' => $subscription->ends_at->format('d M Y'),
                'expired_at' => $subscription->ends_at->format('d M Y'),
                'renew_url' => route('dashboard.shop'),
                'offer_code' => '',
            ],
        );
    }

    public function sendReferralBonusReminder(User $user, string $presetKey, int $dayIndex): void
    {
        $bonus = app(ReferralSignupBonusService::class);

        if (! $bonus->isEnabled() || $bonus->hasCompletedPurchase($user)) {
            return;
        }

        if (! $user->referral_bonus_expires_at || $user->referral_bonus_expires_at->isPast()) {
            return;
        }

        $daysLeft = max(0, (int) now()->diffInDays($user->referral_bonus_expires_at, false));

        $this->sendOnce(
            user: $user,
            presetKey: $presetKey,
            referenceType: 'referral_bonus',
            referenceId: $user->id * 10 + $dayIndex,
            data: [
                'name' => $user->name,
                'discount_percent' => (string) $bonus->percent(),
                'days_left' => (string) $daysLeft,
                'expires_at' => $user->referral_bonus_expires_at->format('d M Y, h:i A'),
                'shop_url' => route('dashboard.shop'),
            ],
        );
    }

    public function sendAffiliateMonthlyReport(User $user, bool $force = false, ?\Carbon\Carbon $forMonth = null): void
    {
        if (! \App\Models\SiteSetting::affiliateConfig()['monthly_email_enabled'] && ! $force) {
            return;
        }

        $reportDate = match (true) {
            $forMonth !== null => $forMonth->copy()->endOfMonth(),
            $force => now(),
            default => now()->subMonth()->endOfMonth(),
        };

        $affiliates = app(AffiliateService::class);

        if (! $force && ! $affiliates->qualifiesForMonthlyReportEmail($user, $reportDate->copy()->startOfMonth())) {
            return;
        }

        $statement = $affiliates->monthlyStatement($user, $reportDate);
        $periodKey = $reportDate->format('Y-m');

        if ($force) {
            $this->mailPanel->sendByPreset(EmailPreset::KEY_AFFILIATE_MONTHLY_REPORT, $user->email, array_merge(
                ['name' => $user->name, 'affiliates_url' => route('dashboard.affiliates')],
                $statement,
            ));

            return;
        }

        $this->sendOnce(
            user: $user,
            presetKey: EmailPreset::KEY_AFFILIATE_MONTHLY_REPORT,
            referenceType: 'affiliate_monthly',
            referenceId: (int) str_replace('-', '', $periodKey),
            data: array_merge(
                ['name' => $user->name, 'affiliates_url' => route('dashboard.affiliates')],
                $statement,
            ),
        );
    }

    public function sendAffiliatePromoEmail(User $user, string $presetKey): void
    {
        if (! in_array($presetKey, [
            EmailPreset::KEY_AFFILIATE_PROGRAM_INVITE,
            EmailPreset::KEY_AFFILIATE_PROGRAM_BOOST,
        ], true)) {
            throw new \InvalidArgumentException('Invalid affiliate promo preset.');
        }

        if (! $this->mailPanel->isEnabled()) {
            return;
        }

        $data = app(AffiliateService::class)->affiliatePromoDataForUser($user);

        $this->mailPanel->sendByPreset($presetKey, $user->email, $data);
    }

    public function announceNewTool(Tool $tool): void
    {
        if (! $tool->is_active) {
            return;
        }

        $users = User::query()
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'active')->where('ends_at', '>', now());
            })
            ->get(['id', 'name', 'email']);

        foreach ($users as $user) {
            $this->sendOnce(
                user: $user,
                presetKey: EmailPreset::KEY_NEW_TOOL,
                referenceType: 'tool',
                referenceId: $tool->id,
                data: [
                    'name' => $user->name,
                    'tool_name' => $tool->name,
                    'tool_description' => $tool->description ?? $tool->shop_tagline ?? 'Now available on your Semrushtoolz dashboard.',
                    'tool_url' => route('dashboard.tools'),
                    'plan_name' => app(SubscriptionService::class)->displayPlanName($user),
                ],
            );
        }
    }

    private function sendOnce(
        User $user,
        string $presetKey,
        string $referenceType,
        int $referenceId,
        array $data,
    ): void {
        if (! $this->mailPanel->isEnabled()) {
            return;
        }

        if (EmailNotificationLog::alreadySent($user->id, $presetKey, $referenceType, $referenceId)) {
            return;
        }

        try {
            $this->mailPanel->sendByPreset($presetKey, $user->email, $data);
            EmailNotificationLog::record($user->id, $presetKey, $referenceType, $referenceId);
        } catch (\Throwable $exception) {
            Log::warning('Transactional email failed', [
                'preset' => $presetKey,
                'user_id' => $user->id,
                'reference' => "{$referenceType}:{$referenceId}",
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function formatAmount(float|string $amount, string $currency): string
    {
        $decimals = $currency === 'usd' && (float) $amount < 100 ? 2 : 0;

        return number_format((float) $amount, $decimals);
    }

    private function paymentUrl(Order $order): string
    {
        return match ($order->payment_method) {
            'upi' => route('dashboard.orders.pay.upi', $order),
            'offline' => route('dashboard.orders.pay.offline', $order),
            default => route('dashboard.orders.pay.paypal', $order),
        };
    }
}
