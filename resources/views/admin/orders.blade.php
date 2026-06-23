@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="dash-page-title !mb-0">Orders</h1>
        <a href="{{ route('admin.orders.export', request()->only(['q', 'status', 'method'])) }}" class="ui-btn-outline text-sm">Export CSV</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="ui-label text-xs">Search</label>
            <input class="ui-input w-48 py-1.5 text-sm" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order #, user…">
        </div>
        <div>
            <label class="ui-label text-xs">Status</label>
            <select class="ui-input w-auto py-1.5 text-sm" name="status">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Status</option>
                @foreach (['pending', 'awaiting_payment', 'awaiting_proof', 'verifying', 'completed', 'failed', 'rejected', 'cancelled', 'refunded'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="ui-label text-xs">Method</label>
            <select class="ui-input w-auto py-1.5 text-sm" name="method">
                <option value="all" @selected(($filters['method'] ?? 'all') === 'all')>All Methods</option>
                <option value="upi" @selected(($filters['method'] ?? '') === 'upi')>UPI</option>
                <option value="paypal" @selected(($filters['method'] ?? '') === 'paypal')>PayPal</option>
                <option value="offline" @selected(($filters['method'] ?? '') === 'offline')>Offline</option>
                <option value="wallet" @selected(($filters['method'] ?? '') === 'wallet')>Wallet</option>
            </select>
        </div>
        <button type="submit" class="ui-btn-outline text-sm">Filter</button>
    </form>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Order</th><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-medium text-ink">{{ $order['id'] }}</td>
                        <td>{{ $order['user'] }}</td>
                        <td>{{ $order['plan'] }}</td>
                        <td>{{ $order['amount'] }}</td>
                        <td>{{ $order['method'] }}</td>
                        <td>
                            <span @class([
                                'dash-badge-online' => $order['status_raw'] === 'completed',
                                'dash-badge-pending' => !in_array($order['status_raw'], ['completed', 'rejected', 'cancelled', 'failed', 'refunded']),
                                'dash-badge-offline' => in_array($order['status_raw'], ['rejected', 'cancelled', 'failed', 'refunded']),
                            ])>{{ $order['status'] }}</span>
                        </td>
                        <td>{{ $order['date'] }}</td>
                        <td class="space-x-1 whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $order['order_id']) }}" class="ui-btn-ghost text-xs">Review</a>
                            @if ($order['can_invoice'])
                                <a href="{{ route('admin.orders.invoice', $order['order_id']) }}" class="ui-btn-ghost text-xs">Invoice</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-ink-muted">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$orders" :per-page="$perPage" />
    </div>
@endsection
