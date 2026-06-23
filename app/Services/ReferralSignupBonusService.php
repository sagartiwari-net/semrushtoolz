<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;

class ReferralSignupBonusService
{
    public function config(): array
    {
        return SiteSetting::affiliateConfig();
    }

    public function isEnabled(): bool
    {
        $config = $this->config();

        return $config['signup_bonus_enabled'] && $config['signup_bonus_percent'] > 0;
    }

    public function percent(): int
    {
        return (int) round($this->config()['signup_bonus_percent'] * 100);
    }

    public function validDays(): int
    {
        return $this->config()['signup_bonus_days'];
    }

    public function expiresAtForNewReferral(): ?\Illuminate\Support\Carbon
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return now()->addDays($this->validDays());
    }

    public function eligible(User $user, int $durationMonths = 1): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if (! $user->referred_by_user_id) {
            return false;
        }

        if ($durationMonths !== 1) {
            return false;
        }

        if (! $user->referral_bonus_expires_at || $user->referral_bonus_expires_at->isPast()) {
            return false;
        }

        return ! $this->hasCompletedPurchase($user);
    }

    public function hasCompletedPurchase(User $user): bool
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();
    }

    public function applyToTotals(array $totals, User $user, string $currency, int $durationMonths): array
    {
        if (! $this->eligible($user, $durationMonths)) {
            return array_merge($totals, [
                'referral_bonus_discount' => 0.0,
                'referral_bonus_percent' => null,
            ]);
        }

        $rate = $this->config()['signup_bonus_percent'];
        $decimals = $currency === 'usd' && (float) $totals['total'] < 100 ? 2 : 0;
        $bonusDiscount = min((float) $totals['total'], round((float) $totals['total'] * $rate, $decimals));
        $total = max(0, round((float) $totals['total'] - $bonusDiscount, $decimals));

        return array_merge($totals, [
            'referral_bonus_discount' => $bonusDiscount,
            'referral_bonus_percent' => $this->percent(),
            'discount' => round((float) $totals['discount'] + $bonusDiscount, 2),
            'total' => $total,
        ]);
    }

    public function daysRemaining(User $user): ?int
    {
        if (! $user->referral_bonus_expires_at || $user->referral_bonus_expires_at->isPast()) {
            return null;
        }

        return max(0, (int) now()->diffInDays($user->referral_bonus_expires_at, false));
    }

    /** @return array{show: bool, percent: int, days: int, referrer_name: ?string}|null */
    public function signupBanner(?string $referralCode, ?string $referrerName): ?array
    {
        if (! $referralCode || ! $this->isEnabled()) {
            return null;
        }

        return [
            'show' => true,
            'percent' => $this->percent(),
            'days' => $this->validDays(),
            'referrer_name' => $referrerName,
        ];
    }
}
