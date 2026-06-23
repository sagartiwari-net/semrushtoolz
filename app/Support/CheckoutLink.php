<?php

namespace App\Support;

class CheckoutLink
{
    public static function forPlan(string $planSlug, int $durationMonths = 1, string $currency = 'inr'): string
    {
        return route('subscribe', [
            'plan' => $planSlug,
            'duration_months' => $durationMonths,
            'currency' => $currency,
        ]);
    }

    public static function forTool(string $toolSlug, int $durationMonths = 1, string $currency = 'inr'): string
    {
        return route('subscribe', [
            'tool' => $toolSlug,
            'duration_months' => $durationMonths,
            'currency' => $currency,
        ]);
    }

    public static function forTrialPlan(string $planSlug, int $durationDays = 1, string $currency = 'inr'): string
    {
        return route('subscribe', [
            'plan' => $planSlug,
            'duration_days' => $durationDays,
            'currency' => $currency,
        ]);
    }
}
