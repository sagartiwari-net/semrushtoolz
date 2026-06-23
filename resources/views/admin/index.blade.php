@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="dash-stats-grid">
        @foreach ($stats as $stat)
            <x-dashboard.stat-card :label="$stat['label']" :value="$stat['value']" :sub="$stat['sub']" :icon="$stat['icon']">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/></svg>
            </x-dashboard.stat-card>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="dash-card">
            <h3 class="dash-card-title">Pending Actions</h3>
            <div class="space-y-3">
                @foreach ($pendingActions as $action)
                    <a href="{{ route($action['route']) }}" class="flex items-center justify-between rounded-xl border border-line px-4 py-3 transition hover:border-accent/30 hover:bg-accent/5">
                        <span class="text-sm font-medium text-ink">{{ $action['label'] }}</span>
                        <span @class(['ui-badge', $action['count'] > 0 ? 'bg-warning/10 text-warning' : 'bg-surface text-ink-muted'])>{{ $action['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="dash-card">
            <h3 class="dash-card-title">System Health</h3>
            <div class="space-y-3 text-sm">
                @foreach ($systemHealth as $health)
                    <div class="flex items-center justify-between rounded-xl bg-surface px-4 py-2.5">
                        <span class="text-ink-secondary">{{ $health['label'] }}</span>
                        <span @class([
                            'dash-badge-online' => $health['ok'] ?? false,
                            'dash-badge-offline' => ! ($health['ok'] ?? false),
                        ])>{{ $health['status'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="dash-card">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="dash-card-title !mb-0">Recent Orders</h3>
            <a href="{{ route('admin.orders') }}" class="text-sm text-accent hover:underline">View all</a>
        </div>
        <div class="dash-table-wrap border-0 shadow-none">
            <table class="dash-table">
                <thead><tr><th>Order</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="font-medium text-ink">
                                @if (! empty($order['order_id']))
                                    <a href="{{ route('admin.orders.show', $order['order_id']) }}" class="hover:text-accent">{{ $order['id'] }}</a>
                                @else
                                    {{ $order['id'] }}
                                @endif
                            </td>
                            <td>{{ $order['plan'] }}</td>
                            <td>{{ $order['amount'] }}</td>
                            <td>{{ $order['method'] }}</td>
                            <td><span @class(['dash-badge-online' => $order['status_raw'] === 'completed', 'dash-badge-pending' => $order['status_raw'] !== 'completed'])>{{ $order['status'] }}</span></td>
                            <td>{{ $order['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-ink-muted">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
