<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Tool;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminEarningsReportService
{
    /**
     * @return array{
     *   filters: array,
     *   from: Carbon,
     *   to: Carbon,
     *   summary: array,
     *   byMethod: Collection,
     *   byItem: Collection,
     *   byMonth: Collection,
     *   tools: Collection,
     *   plans: Collection
     * }
     */
    public function report(Request $request): array
    {
        [$from, $to, $filters] = $this->resolveRange($request);

        $base = $this->baseQuery($from, $to, $filters);

        $summary = [
            'orders' => (clone $base)->count(),
            'inr' => (float) (clone $base)->where('currency', 'inr')->sum('total'),
            'usd' => (float) (clone $base)->where('currency', 'usd')->sum('total'),
            'refunded_orders' => Order::query()
                ->where('status', 'refunded')
                ->where(function ($q) use ($from, $to) {
                    $q->whereBetween('paid_at', [$from, $to])
                        ->orWhere(function ($q2) use ($from, $to) {
                            $q2->whereNull('paid_at')->whereBetween('updated_at', [$from, $to]);
                        });
                })
                ->count(),
        ];

        $byMethod = (clone $base)
            ->selectRaw('payment_method, currency, COUNT(*) as orders_count, SUM(total) as revenue')
            ->groupBy('payment_method', 'currency')
            ->orderBy('currency')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'method' => $this->methodLabel($row->payment_method),
                'method_raw' => $row->payment_method ?: '—',
                'currency' => $row->currency,
                'orders' => (int) $row->orders_count,
                'revenue' => (float) $row->revenue,
                'formatted' => $this->formatMoney((float) $row->revenue, $row->currency),
            ]);

        $byItemRows = (clone $base)
            ->selectRaw('plan_id, tool_id, order_type, currency, COUNT(*) as orders_count, SUM(total) as revenue')
            ->groupBy('plan_id', 'tool_id', 'order_type', 'currency')
            ->orderByDesc('revenue')
            ->get();

        $planNames = Plan::query()->whereIn('id', $byItemRows->pluck('plan_id')->filter()->unique())->pluck('name', 'id');
        $toolNames = Tool::query()->whereIn('id', $byItemRows->pluck('tool_id')->filter()->unique())->pluck('name', 'id');

        $byItem = $byItemRows->map(function ($row) use ($planNames, $toolNames) {
            $name = $planNames[$row->plan_id]
                ?? $toolNames[$row->tool_id]
                ?? match ($row->order_type) {
                    'wallet_topup' => 'Wallet Top-up',
                    'reseller_balance_topup' => 'Reseller Balance Top-up',
                    default => 'Unknown / deleted item',
                };

            return [
                'name' => $name,
                'type' => $row->plan_id ? 'Plan' : ($row->tool_id ? 'Tool' : 'Other'),
                'currency' => $row->currency,
                'orders' => (int) $row->orders_count,
                'revenue' => (float) $row->revenue,
                'formatted' => $this->formatMoney((float) $row->revenue, $row->currency),
            ];
        });

        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', COALESCE(paid_at, created_at))"
            : "DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m')";

        $byMonth = (clone $base)
            ->selectRaw("{$monthExpr} as month_key, currency, COUNT(*) as orders_count, SUM(total) as revenue")
            ->groupBy('month_key', 'currency')
            ->orderByDesc('month_key')
            ->get()
            ->map(function ($row) {
                $month = (string) $row->month_key;

                return [
                    'month' => $month,
                    'month_label' => $month !== ''
                        ? Carbon::createFromFormat('Y-m', $month)->format('M Y')
                        : 'Unknown',
                    'currency' => $row->currency,
                    'orders' => (int) $row->orders_count,
                    'revenue' => (float) $row->revenue,
                    'formatted' => $this->formatMoney((float) $row->revenue, $row->currency),
                ];
            });

        return [
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'byMethod' => $byMethod,
            'byItem' => $byItem,
            'byMonth' => $byMonth,
            'tools' => Tool::query()->where('show_in_shop', true)->orderBy('sort_order')->get(['id', 'name']),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
        ];
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to, $filters] = $this->resolveRange($request);
        $query = $this->baseQuery($from, $to, $filters)
            ->with(['user:id,name,email', 'plan:id,name', 'tool:id,name'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        $filename = 'earnings-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Order', 'User', 'Email', 'Item', 'Type', 'Method', 'Currency', 'Total', 'Paid At', 'Created At',
            ]);

            $query->chunkById(200, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->order_number,
                        $order->user?->name,
                        $order->user?->email,
                        $order->purchasedItemName(),
                        $order->plan_id ? 'plan' : ($order->tool_id ? 'tool' : ($order->order_type ?: 'other')),
                        $order->payment_method,
                        $order->currency,
                        number_format((float) $order->total, 2, '.', ''),
                        optional($order->paid_at)?->toDateTimeString(),
                        optional($order->created_at)?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: array}
     */
    protected function resolveRange(Request $request): array
    {
        $preset = $request->input('preset', 'this_month');
        $now = now();

        [$from, $to] = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'custom' => [
                $request->filled('from')
                    ? Carbon::parse($request->input('from'))->startOfDay()
                    : $now->copy()->startOfMonth(),
                $request->filled('to')
                    ? Carbon::parse($request->input('to'))->endOfDay()
                    : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()], // this_month
        };

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $filters = [
            'preset' => $preset,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'method' => $request->input('method', 'all'),
            'currency' => $request->input('currency', 'all'),
            'tool_id' => $request->input('tool_id', ''),
            'plan_id' => $request->input('plan_id', ''),
            'include_topups' => $request->boolean('include_topups'),
        ];

        return [$from, $to, $filters];
    }

    protected function baseQuery(Carbon $from, Carbon $to, array $filters)
    {
        $query = Order::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->whereNull('paid_at')->whereBetween('created_at', [$from, $to]);
                    });
            });

        if (! ($filters['include_topups'] ?? false)) {
            $query->where(function ($q) {
                $q->whereNull('order_type')
                    ->orWhereNotIn('order_type', ['wallet_topup', 'reseller_balance_topup']);
            });
        }

        if (($filters['method'] ?? 'all') !== 'all') {
            $query->where('payment_method', $filters['method']);
        }

        if (($filters['currency'] ?? 'all') !== 'all') {
            $query->where('currency', $filters['currency']);
        }

        if (filled($filters['tool_id'] ?? null)) {
            $query->where('tool_id', (int) $filters['tool_id']);
        }

        if (filled($filters['plan_id'] ?? null)) {
            $query->where('plan_id', (int) $filters['plan_id']);
        }

        return $query;
    }

    protected function methodLabel(?string $method): string
    {
        return match ($method) {
            'upi' => 'UPI',
            'paypal' => 'PayPal',
            'offline' => 'Offline',
            'wallet' => 'Wallet',
            default => $method ? ucfirst($method) : '—',
        };
    }

    protected function formatMoney(float $amount, string $currency): string
    {
        $symbol = $currency === 'usd' ? '$' : '₹';
        $decimals = $currency === 'usd' ? 2 : 0;

        return $symbol.number_format($amount, $decimals);
    }
}
