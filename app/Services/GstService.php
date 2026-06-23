<?php

namespace App\Services;

use App\Models\SiteSetting;

class GstService
{
    public static function config(): array
    {
        return SiteSetting::gstConfig();
    }

    public static function isEnabledForCurrency(string $currency): bool
    {
        $config = static::config();

        return $config['enabled'] && strtolower($currency) === 'inr';
    }

    public static function applyToTotals(array $totals, string $currency, int $durationMonths = 1): array
    {
        $config = static::config();
        $decimals = strtolower($currency) === 'usd' && (float) ($totals['total'] ?? 0) < 100 ? 2 : 0;

        $totals['gst_rate'] = null;
        $totals['gst_amount'] = 0.0;
        $totals['taxable_amount'] = round((float) $totals['total'], $decimals);

        if (! static::isEnabledForCurrency($currency)) {
            return $totals;
        }

        $rate = (float) $config['rate'];
        if ($rate <= 0) {
            return $totals;
        }

        $taxable = round((float) $totals['total'], 2);
        $gstAmount = round($taxable * $rate / 100, 2);

        $totals['gst_rate'] = $rate;
        $totals['gst_amount'] = $gstAmount;
        $totals['taxable_amount'] = $taxable;
        $totals['total'] = round($taxable + $gstAmount, 2);

        if ($durationMonths > 1) {
            $totals['per_month'] = round($totals['total'] / $durationMonths, $decimals);
        }

        return $totals;
    }
}
