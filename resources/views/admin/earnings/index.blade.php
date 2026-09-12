@extends('layouts.admin')

@section('title', 'Earnings')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="dash-page-title !mb-1">Earnings report</h1>
            <p class="text-sm text-ink-secondary">
                Completed payments from
                <strong>{{ $from->format('M j, Y') }}</strong>
                to
                <strong>{{ $to->format('M j, Y') }}</strong>
            </p>
        </div>
        <a href="{{ route('admin.earnings.export', request()->query()) }}" class="ui-btn-outline text-sm">Export CSV</a>
    </div>

    <form method="GET" action="{{ route('admin.earnings') }}" class="mb-5 space-y-3">
        <div class="flex flex-wrap items-end gap-2">
            <div>
                <label class="ui-label text-xs">Period</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="preset" id="earnings-preset">
                    @foreach ([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'last_7' => 'Last 7 days',
                        'last_30' => 'Last 30 days',
                        'this_month' => 'This month',
                        'last_month' => 'Last month',
                        'this_year' => 'This year',
                        'custom' => 'Custom dates',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['preset'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">From</label>
                <input class="ui-input py-1.5 text-sm" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div>
                <label class="ui-label text-xs">To</label>
                <input class="ui-input py-1.5 text-sm" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div>
                <label class="ui-label text-xs">Method</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="method">
                    <option value="all" @selected(($filters['method'] ?? 'all') === 'all')>All methods</option>
                    <option value="upi" @selected(($filters['method'] ?? '') === 'upi')>UPI</option>
                    <option value="paypal" @selected(($filters['method'] ?? '') === 'paypal')>PayPal</option>
                    <option value="offline" @selected(($filters['method'] ?? '') === 'offline')>Offline</option>
                    <option value="wallet" @selected(($filters['method'] ?? '') === 'wallet')>Wallet</option>
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Currency</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="currency">
                    <option value="all" @selected(($filters['currency'] ?? 'all') === 'all')>All</option>
                    <option value="inr" @selected(($filters['currency'] ?? '') === 'inr')>INR</option>
                    <option value="usd" @selected(($filters['currency'] ?? '') === 'usd')>USD</option>
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Tool</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="tool_id">
                    <option value="">All tools</option>
                    @foreach ($tools as $tool)
                        <option value="{{ $tool->id }}" @selected((string) ($filters['tool_id'] ?? '') === (string) $tool->id)>{{ $tool->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Plan</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="plan_id">
                    <option value="">All plans</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="mb-1.5 inline-flex items-center gap-2 text-sm text-ink-secondary">
                <input type="checkbox" name="include_topups" value="1" @checked(!empty($filters['include_topups']))>
                Include wallet / reseller top-ups
            </label>
            <button type="submit" class="ui-btn-outline text-sm">Apply</button>
            <a href="{{ route('admin.earnings') }}" class="ui-btn-ghost text-sm">Reset</a>
        </div>
    </form>

    <div class="dash-stats-grid-3up mb-6">
        <x-dashboard.stat-card label="Orders" :value="number_format($summary['orders'])" icon="blue" sub="Completed in selected period">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Revenue INR" :value="'₹'.number_format($summary['inr'], 0)" icon="green" sub="UPI / Offline / Wallet (INR)">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Revenue USD" :value="'$'.number_format($summary['usd'], 2)" icon="purple" sub="PayPal (USD)">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
        </x-dashboard.stat-card>
    </div>

    @if ($summary['refunded_orders'] > 0)
        <p class="mb-4 text-xs text-ink-muted">Note: {{ $summary['refunded_orders'] }} refunded order(s) in this period are not counted in revenue (only completed).</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="dash-card">
            <h2 class="dash-card-title">By payment method</h2>
            <div class="dash-table-wrap">
                <table class="dash-table text-sm">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Currency</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byMethod as $row)
                            <tr>
                                <td class="font-medium">{{ $row['method'] }}</td>
                                <td>{{ strtoupper($row['currency']) }}</td>
                                <td>{{ number_format($row['orders']) }}</td>
                                <td class="font-semibold">{{ $row['formatted'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-ink-muted">No completed payments in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-card">
            <h2 class="dash-card-title">By month</h2>
            <div class="dash-table-wrap">
                <table class="dash-table text-sm">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Currency</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byMonth as $row)
                            <tr>
                                <td class="font-medium">{{ $row['month_label'] }}</td>
                                <td>{{ strtoupper($row['currency']) }}</td>
                                <td>{{ number_format($row['orders']) }}</td>
                                <td class="font-semibold">{{ $row['formatted'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-ink-muted">No monthly data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dash-card mt-6">
        <h2 class="dash-card-title">By tool / plan</h2>
        <p class="mb-3 text-xs text-ink-muted">Shows which product sold how much in the selected period.</p>
        <div class="dash-table-wrap">
            <table class="dash-table text-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Currency</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byItem as $row)
                        <tr>
                            <td class="font-medium">{{ $row['name'] }}</td>
                            <td>{{ $row['type'] }}</td>
                            <td>{{ strtoupper($row['currency']) }}</td>
                            <td>{{ number_format($row['orders']) }}</td>
                            <td class="font-semibold">{{ $row['formatted'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-ink-muted">No product sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
