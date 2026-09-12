<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\Tool;
use Database\Seeders\ToolSeeder;
use Illuminate\Console\Command;

/**
 * Recreate missing catalog tools and re-link orphaned subscriptions/orders.
 * Does NOT delete or modify users.
 */
class RestoreToolCatalogCommand extends Command
{
    protected $signature = 'tools:restore-catalog
                            {--dry-run : Show what would change without writing}
                            {--skip-seed : Only relink; do not reseed tools}';

    protected $description = 'Restore missing shop tools (Ahrefs plans, etc.) and re-link orphaned subscriptions/orders. Never touches users.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if (! $this->option('skip-seed')) {
            $this->info($dry ? '[dry-run] Would reseed ToolSeeder catalog…' : 'Reseeding tool catalog (updateOrCreate by slug)…');
            if (! $dry) {
                $this->call('db:seed', ['--class' => ToolSeeder::class, '--force' => true]);
            }
        }

        $tools = Tool::query()->get()->keyBy('slug');
        $this->table(
            ['slug', 'id', 'name', 'INR', 'USD', 'shop'],
            $tools->map(fn (Tool $t) => [
                $t->slug,
                $t->id,
                $t->name,
                $t->price_inr,
                $t->price_usd,
                $t->show_in_shop ? 'yes' : 'no',
            ])->values()->all()
        );

        $priceMap = $this->buildPriceMap($tools);
        $relinkedOrders = 0;
        $relinkedSubs = 0;
        $unmatched = [];

        // Completed tool orders that lost tool_id after cascade nullOnDelete.
        $orphanOrders = Order::query()
            ->whereNull('tool_id')
            ->whereNull('plan_id')
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('order_type', 'subscription')
                    ->orWhereNull('order_type');
            })
            ->orderBy('id')
            ->get();

        $this->info("Orphan completed tool orders: {$orphanOrders->count()}");

        foreach ($orphanOrders as $order) {
            $slug = $this->guessToolSlug($order, $priceMap);
            if (! $slug || ! $tools->has($slug)) {
                $unmatched[] = "order {$order->order_number} total={$order->total} {$order->currency} months={$order->duration_months}";
                continue;
            }

            $toolId = (int) $tools[$slug]->id;
            $this->line("  order {$order->order_number} → {$slug} (#{$toolId})");

            if (! $dry) {
                $order->update(['tool_id' => $toolId]);

                if ($order->subscription_id) {
                    Subscription::query()
                        ->where('id', $order->subscription_id)
                        ->whereNull('tool_id')
                        ->update(['tool_id' => $toolId]);
                } else {
                    // Match subscription by user + dates when order.subscription_id missing.
                    Subscription::query()
                        ->where('user_id', $order->user_id)
                        ->whereNull('tool_id')
                        ->whereNull('plan_id')
                        ->where('amount_paid', $order->total)
                        ->whereDate('starts_at', optional($order->paid_at ?? $order->created_at)?->toDateString())
                        ->limit(1)
                        ->update(['tool_id' => $toolId]);
                }
            }

            $relinkedOrders++;
        }

        // Active subscriptions still missing tool_id (admin grants / unmatched).
        $orphanSubs = Subscription::query()
            ->whereNull('tool_id')
            ->whereNull('plan_id')
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderBy('id')
            ->get();

        $this->info("Active orphan subscriptions still missing tool: {$orphanSubs->count()}");

        foreach ($orphanSubs as $sub) {
            $slug = $this->guessToolSlugFromSubscription($sub, $priceMap);
            if (! $slug || ! $tools->has($slug)) {
                $unmatched[] = "sub #{$sub->id} user={$sub->user_id} paid={$sub->amount_paid} months={$sub->duration_months}";
                continue;
            }

            $toolId = (int) $tools[$slug]->id;
            $this->line("  subscription #{$sub->id} → {$slug} (#{$toolId})");

            if (! $dry) {
                $sub->update(['tool_id' => $toolId]);
            }

            $relinkedSubs++;
        }

        $this->newLine();
        $this->info(($dry ? '[dry-run] Would relink' : 'Relinked')." orders={$relinkedOrders}, subscriptions={$relinkedSubs}");

        if ($unmatched !== []) {
            $this->warn('Could not auto-match '.count($unmatched).' row(s) — check manually:');
            foreach (array_slice($unmatched, 0, 40) as $line) {
                $this->line('  '.$line);
            }
            if (count($unmatched) > 40) {
                $this->line('  … and '.(count($unmatched) - 40).' more');
            }
        }

        // Never touch users.
        $this->info('Users table was not modified.');

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Tool>  $tools
     * @return array<string, list<array{slug:string,months:int,currency:string,total:float}>>
     */
    protected function buildPriceMap($tools): array
    {
        $durations = config('pricing.durations', [1 => ['discount' => 0]]);
        $map = [];

        foreach ($tools as $slug => $tool) {
            if (! $tool->show_in_shop && ! in_array($slug, ['ahrefs_plan_1', 'ahrefs_plan_2', 'ahrefs_plan_3', 'ahrefs_plan_4', 'semrush', 'semrush_site_audit'], true)) {
                continue;
            }

            foreach (['inr' => (float) $tool->price_inr, 'usd' => (float) $tool->price_usd] as $currency => $monthly) {
                if ($monthly <= 0) {
                    continue;
                }

                foreach ($durations as $months => $meta) {
                    $months = (int) $months;
                    $discount = (float) ($meta['discount'] ?? 0);
                    $subtotal = round($monthly * $months, 2);
                    $total = round($subtotal * (1 - $discount), 2);

                    $key = $currency.':'.number_format($total, 2, '.', '');
                    $map[$key][] = ['slug' => $slug, 'months' => $months, 'currency' => $currency, 'total' => $total];

                    // Also allow raw monthly × months without discount (admin grants / old totals).
                    $raw = round($monthly * $months, 2);
                    $rawKey = $currency.':'.number_format($raw, 2, '.', '');
                    $map[$rawKey][] = ['slug' => $slug, 'months' => $months, 'currency' => $currency, 'total' => $raw];
                }

                // Single-month exact
                $one = $currency.':'.number_format($monthly, 2, '.', '');
                $map[$one][] = ['slug' => $slug, 'months' => 1, 'currency' => $currency, 'total' => $monthly];
            }
        }

        return $map;
    }

    protected function guessToolSlug(Order $order, array $priceMap): ?string
    {
        $currency = strtolower((string) ($order->currency ?: 'inr'));
        $months = max(1, (int) ($order->duration_months ?: 1));

        // Prefer pre-GST amount when present (matches catalog monthly × duration).
        $amounts = array_values(array_unique(array_filter([
            $order->taxable_amount !== null ? round((float) $order->taxable_amount, 2) : null,
            $order->subtotal !== null ? round((float) $order->subtotal, 2) : null,
            round((float) $order->total, 2),
        ], fn ($v) => $v !== null && $v > 0)));

        foreach ($amounts as $total) {
            $key = $currency.':'.number_format($total, 2, '.', '');
            $candidates = collect($priceMap[$key] ?? [])
                ->filter(fn ($row) => (int) $row['months'] === $months || $months === 1)
                ->values();

            if ($candidates->isEmpty()) {
                $candidates = collect($priceMap[$key] ?? [])->values();
            }

            if ($candidates->count() === 1) {
                return $candidates[0]['slug'];
            }

            if ($candidates->count() > 1) {
                $byMonths = $candidates->firstWhere('months', $months);

                return $byMonths['slug'] ?? $candidates[0]['slug'];
            }
        }

        return null;
    }

    protected function guessToolSlugFromSubscription(Subscription $sub, array $priceMap): ?string
    {
        $paid = round((float) $sub->amount_paid, 2);
        if ($paid <= 0) {
            return null;
        }

        $currency = strtolower((string) ($sub->currency ?: 'inr'));
        $months = max(1, (int) ($sub->duration_months ?: 1));
        $key = $currency.':'.number_format($paid, 2, '.', '');

        $candidates = collect($priceMap[$key] ?? [])
            ->filter(fn ($row) => (int) $row['months'] === $months)
            ->values();

        if ($candidates->isEmpty()) {
            $candidates = collect($priceMap[$key] ?? [])->values();
        }

        return $candidates[0]['slug'] ?? null;
    }
}
