<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Collection;

class PricingService
{
    public static function durations(): array
    {
        return config('pricing.durations');
    }

    public static function paymentMethods(): array
    {
        return config('pricing.payment_methods');
    }

    /** Individual sellable tools for shop */
    public static function shopTools(?string $category = null): array
    {
        return self::shopToolsQuery($category)
            ->get()
            ->map->toShopArray()
            ->all();
    }

    public static function shopToolsGrouped(): Collection
    {
        $order = array_keys(Tool::categories());

        return self::shopToolsQuery()
            ->get()
            ->groupBy(fn (Tool $tool) => $tool->category ?: 'other')
            ->map(fn ($tools) => $tools->map->toShopArray()->values())
            ->sortBy(fn ($_, $key) => ($i = array_search($key, $order)) !== false ? $i : 99);
    }

    public static function shopToolsQuery(?string $category = null)
    {
        $query = Tool::query()
            ->where('is_active', true)
            ->where('show_in_shop', true)
            ->where(function ($q) {
                $q->where('price_inr', '>', 0)->orWhere('price_usd', '>', 0);
            })
            ->orderBy('sort_order');

        if ($category) {
            $query->where('category', $category);
        }

        return $query;
    }

    /** Bundle / combo plans only */
    public static function bundlePlans(): array
    {
        return Plan::where('is_active', true)
            ->where('is_bundle', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toPricingArray()
            ->all();
    }

    public static function mainPlans(): array
    {
        return collect(self::shopTools(Tool::CATEGORY_SEO))
            ->reject(fn ($t) => str_starts_with($t['id'] ?? '', 'ahrefs_'))
            ->values()
            ->all();
    }

    public static function ahrefsPlans(): array
    {
        return collect(self::shopTools(Tool::CATEGORY_SEO))
            ->filter(fn ($t) => str_starts_with($t['id'] ?? '', 'ahrefs_'))
            ->values()
            ->all();
    }

    public static function allShopPlans(): array
    {
        return array_merge(self::shopTools(), self::bundlePlans());
    }

    public static function plansBySlugs(array $slugs): array
    {
        $tools = Tool::whereIn('slug', $slugs)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toShopArray()
            ->all();

        if (count($tools) === count($slugs)) {
            return $tools;
        }

        return Plan::whereIn('slug', $slugs)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toPricingArray()
            ->all();
    }

    public static function activePlans(?string $displayGroup = null)
    {
        $query = Plan::where('is_active', true)->where('is_bundle', true)->orderBy('sort_order');

        if ($displayGroup) {
            $query->where('display_group', $displayGroup);
        }

        return $query->get();
    }

    public static function calculatePrice(float $monthly, int $months, float $discount): array
    {
        $subtotal = $monthly * $months;
        $total = round($subtotal * (1 - $discount), $monthly < 100 ? 2 : 0);
        $saved = round($subtotal - $total, $monthly < 100 ? 2 : 0);
        $perMonth = $months > 1 ? round($total / $months, $monthly < 100 ? 2 : 0) : $total;

        return [
            'subtotal' => $subtotal,
            'total' => $total,
            'saved' => $saved,
            'per_month' => $perMonth,
            'discount_percent' => (int) ($discount * 100),
        ];
    }

    public static function jsConfig(): array
    {
        return [
            'durations' => self::durations(),
            'paymentMethods' => self::paymentMethods(),
        ];
    }
}
