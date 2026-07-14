<?php

namespace App\Services;

use App\Models\ResellerPrice;
use App\Models\ResellerToolPrice;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Collection;

class ResellerPricingService
{
    /** Monthly reseller price for a tool, or null if not sellable to this reseller. */
    public function monthlyPriceInr(User $reseller, Tool $tool): ?int
    {
        $override = ResellerToolPrice::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('tool_id', $tool->id)
            ->value('price_inr');

        if ($override !== null) {
            return (int) $override;
        }

        $default = ResellerPrice::query()
            ->where('tool_id', $tool->id)
            ->value('price_inr');

        return $default !== null ? (int) $default : null;
    }

    public function chargeForMonths(User $reseller, Tool $tool, int $months): ?float
    {
        $monthly = $this->monthlyPriceInr($reseller, $tool);
        if ($monthly === null) {
            return null;
        }

        $months = max(1, $months);

        return round($monthly * $months, 2);
    }

    /**
     * Tools available to this reseller with resolved monthly price.
     *
     * @return Collection<int, array{tool: Tool, price_inr: int}>
     */
    public function catalogForReseller(User $reseller): Collection
    {
        $tools = Tool::query()
            ->where('is_active', true)
            ->where('show_in_shop', true)
            ->where(function ($q) {
                $q->where('price_inr', '>', 0)->orWhere('price_usd', '>', 0);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $tools
            ->map(function (Tool $tool) use ($reseller) {
                $price = $this->monthlyPriceInr($reseller, $tool);
                if ($price === null) {
                    return null;
                }

                return [
                    'tool' => $tool,
                    'price_inr' => $price,
                ];
            })
            ->filter()
            ->values();
    }

    public function upsertDefaultPrice(Tool $tool, int $priceInr): ResellerPrice
    {
        return ResellerPrice::updateOrCreate(
            ['tool_id' => $tool->id],
            ['price_inr' => max(0, $priceInr)]
        );
    }

    public function deleteDefaultPrice(Tool $tool): void
    {
        ResellerPrice::where('tool_id', $tool->id)->delete();
    }

    public function upsertResellerPrice(User $reseller, Tool $tool, ?int $priceInr): void
    {
        if ($priceInr === null || $priceInr < 0) {
            ResellerToolPrice::where('reseller_user_id', $reseller->id)
                ->where('tool_id', $tool->id)
                ->delete();

            return;
        }

        ResellerToolPrice::updateOrCreate(
            [
                'reseller_user_id' => $reseller->id,
                'tool_id' => $tool->id,
            ],
            ['price_inr' => $priceInr]
        );
    }

    /** @return array<int, int|null> tool_id => override or null */
    public function overridesMap(User $reseller): array
    {
        return ResellerToolPrice::query()
            ->where('reseller_user_id', $reseller->id)
            ->pluck('price_inr', 'tool_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** @return array<int, int> tool_id => default price */
    public function defaultsMap(): array
    {
        return ResellerPrice::query()
            ->pluck('price_inr', 'tool_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
