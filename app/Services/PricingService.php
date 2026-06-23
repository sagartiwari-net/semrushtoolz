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

    /** Bundle / combo plans only (excludes trial) */
    public static function bundlePlans(): array
    {
        return Plan::where('is_active', true)
            ->where('is_bundle', true)
            ->where('is_trial', false)
            ->orderBy('sort_order')
            ->get()
            ->map->toPricingArray()
            ->all();
    }

    public static function trialDurations(): array
    {
        return config('pricing.trial_durations', []);
    }

    public static function trialPlan(): ?Plan
    {
        return Plan::query()
            ->where('is_active', true)
            ->where('is_trial', true)
            ->orderBy('sort_order')
            ->first();
    }

    public static function trialPlanForHomepage(): ?array
    {
        $plan = self::trialPlan();

        if (! $plan) {
            return null;
        }

        return array_merge($plan->toPricingArray(), [
            'is_trial' => true,
            'checkout_type' => 'plan',
            'features' => collect(config('pricing.trial_plan_features', $plan->features ?? []))
                ->map(fn ($f) => is_array($f) ? $f : ['text' => $f])
                ->all(),
        ]);
    }

    public static function mainPlans(): array
    {
        return self::shopToolsQuery()
            ->where('slug', 'not like', 'ahrefs%')
            ->get()
            ->map->toShopArray()
            ->all();
    }

    public static function ahrefsPlans(): array
    {
        return self::shopToolsQuery()
            ->where('slug', 'like', 'ahrefs_plan_%')
            ->get()
            ->map->toShopArray()
            ->all();
    }

    /** Semrush + combo + site audit — homepage featured row (plan-card layout) */
    public static function homepageSemrushPlans(): array
    {
        $order = ['semrush', 'combo', 'semrush_site_audit'];
        $plans = [];

        $tools = self::shopToolsQuery()
            ->whereIn('slug', ['semrush', 'semrush_site_audit'])
            ->get()
            ->keyBy('slug');

        foreach (['semrush', 'semrush_site_audit'] as $slug) {
            if ($tool = $tools->get($slug)) {
                $plans[$slug] = self::toolToPlanArray($tool);
            }
        }

        $combo = collect(self::bundlePlans())->firstWhere('id', 'combo');
        if ($combo) {
            $plans['combo'] = array_merge($combo, ['checkout_type' => 'plan']);
        }

        return collect($order)
            ->map(fn (string $slug) => $plans[$slug] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    public static function toolToPlanArray(Tool $tool): array
    {
        $config = collect(config('pricing.main_plans'))->firstWhere('id', $tool->slug);

        $features = $config['features'] ?? $tool->shop_features ?? [];
        $features = collect($features)
            ->map(fn ($f) => is_array($f) ? $f : ['text' => $f])
            ->all();

        return [
            'id' => $tool->slug,
            'name' => $config['name'] ?? $tool->name,
            'tagline' => $config['tagline'] ?? $tool->description,
            'price_inr' => $tool->price_inr,
            'price_usd' => $tool->price_usd,
            'logo' => $config['logo'] ?? $tool->thumbnailUrl(),
            'logo_alt' => $config['logo_alt'] ?? $tool->name,
            'featured' => (bool) ($config['featured'] ?? false),
            'badge' => $config['badge'] ?? $tool->shop_badge,
            'features' => $features,
            'checkout_type' => 'tool',
        ];
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
            'trialDurations' => self::trialDurations(),
            'paymentMethods' => self::paymentMethods(),
        ];
    }
}
