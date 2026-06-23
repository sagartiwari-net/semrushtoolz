<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function __construct(
        protected SubscriptionService $subscriptions
    ) {}

    public function findValidCode(string $code): ?Coupon
    {
        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->first();

        if (! $coupon || ! $coupon->isWithinDateRange() || ! $coupon->hasUsesRemaining()) {
            return null;
        }

        return $coupon;
    }

    public function validateForCheckout(
        Coupon $coupon,
        User $user,
        array $totals,
        string $currency,
        ?int $planId = null,
        ?int $toolId = null,
        int $durationMonths = 1,
    ): void {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is disabled.']);
        }

        if (! $coupon->isWithinDateRange()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon has expired or is not active yet.']);
        }

        if (! $coupon->hasUsesRemaining()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon has reached its usage limit.']);
        }

        if ($coupon->max_uses_per_user !== null) {
            $userUses = Order::query()
                ->where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->whereNotIn('status', ['cancelled', 'rejected', 'failed'])
                ->count();

            if ($userUses >= $coupon->max_uses_per_user) {
                throw ValidationException::withMessages(['coupon_code' => 'You have already used this coupon the maximum number of times.']);
            }
        }

        $this->validateProductRules($coupon, $planId, $toolId);
        $this->validateDurationRules($coupon, $durationMonths);
        $this->validateRequiredSubscription($coupon, $user);

        if ($coupon->type === Coupon::TYPE_FIXED && $coupon->currency && $coupon->currency !== $currency) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is not valid for '.strtoupper($currency).' orders.']);
        }

        $payableBeforeCoupon = (float) $totals['total'];

        if ($coupon->min_order_amount !== null && $payableBeforeCoupon < (float) $coupon->min_order_amount) {
            $symbol = $currency === 'usd' ? '$' : '₹';
            throw ValidationException::withMessages([
                'coupon_code' => 'Minimum order amount is '.$symbol.number_format((float) $coupon->min_order_amount, 0).' (after duration discounts).',
            ]);
        }
    }

    protected function validateProductRules(Coupon $coupon, ?int $planId, ?int $toolId): void
    {
        if (! $coupon->hasProductRestrictions()) {
            return;
        }

        $allowed = false;

        if ($planId && $coupon->hasAllowedPlans() && in_array($planId, $coupon->allowed_plan_ids, true)) {
            $allowed = true;
        }

        if ($toolId && $coupon->hasAllowedTools() && in_array($toolId, $coupon->allowed_tool_ids, true)) {
            $allowed = true;
        }

        if (! $allowed) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is not valid for the selected product or plan.']);
        }
    }

    protected function validateDurationRules(Coupon $coupon, int $durationMonths): void
    {
        if (! $coupon->hasDurationRestrictions()) {
            return;
        }

        $allowed = array_map('intval', $coupon->allowed_duration_months);

        if (! in_array($durationMonths, $allowed, true)) {
            $labels = collect($allowed)
                ->map(fn ($m) => config("pricing.durations.{$m}.label", "{$m} months"))
                ->implode(', ');

            throw ValidationException::withMessages([
                'coupon_code' => "This coupon only applies to: {$labels}.",
            ]);
        }
    }

    protected function validateRequiredSubscription(Coupon $coupon, User $user): void
    {
        if (! $coupon->hasRequiredSubscription()) {
            return;
        }

        foreach ($this->subscriptions->activeSubscriptions($user) as $sub) {
            if ($sub->plan_id && $coupon->hasRequiredPlans() && in_array($sub->plan_id, $coupon->required_plan_ids, true)) {
                return;
            }

            if ($sub->tool_id && $coupon->hasRequiredTools() && in_array($sub->tool_id, $coupon->required_tool_ids, true)) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'coupon_code' => 'You need an active subscription that matches this coupon\'s requirements.',
        ]);
    }

    public function discountAmount(Coupon $coupon, float $payableAmount, string $currency): float
    {
        if ($payableAmount <= 0) {
            return 0.0;
        }

        $decimals = $currency === 'usd' && $payableAmount < 100 ? 2 : 0;

        if ($coupon->type === Coupon::TYPE_PERCENT) {
            return min($payableAmount, round($payableAmount * ((float) $coupon->value / 100), $decimals));
        }

        return min($payableAmount, round((float) $coupon->value, $decimals));
    }

    public function applyToTotals(array $totals, ?Coupon $coupon, string $currency): array
    {
        if (! $coupon) {
            return array_merge($totals, [
                'coupon' => null,
                'coupon_code' => null,
                'coupon_discount' => 0.0,
            ]);
        }

        $couponDiscount = $this->discountAmount($coupon, (float) $totals['total'], $currency);
        $total = max(0, round((float) $totals['total'] - $couponDiscount, $currency === 'usd' && $totals['total'] < 100 ? 2 : 0));

        return array_merge($totals, [
            'coupon' => $coupon,
            'coupon_code' => $coupon->code,
            'coupon_discount' => $couponDiscount,
            'discount' => round((float) $totals['discount'] + $couponDiscount, 2),
            'total' => $total,
        ]);
    }

    public function recordUse(Coupon $coupon): void
    {
        $coupon->increment('used_count');
    }
}
