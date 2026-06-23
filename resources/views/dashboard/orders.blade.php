@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h1 class="dash-page-title !mb-0">Orders</h1>
        <a href="{{ route('dashboard.shop') }}" class="ui-btn-primary">Buy New Plan</a>
        @if (\App\Models\SiteSetting::walletConfig()['enabled'])
            <a href="{{ route('dashboard.wallet.topup') }}" class="ui-btn-outline">Add Wallet Money</a>
        @endif
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-medium text-ink">{{ $order['id'] }}</td>
                        <td>{{ $order['plan'] }}</td>
                        <td class="font-semibold text-ink">{{ $order['amount'] }}</td>
                        <td>{{ $order['method'] }}</td>
                        <td>
                            @if ($order['status_raw'] === 'completed')
                                <span class="dash-badge-online">{{ $order['status'] }}</span>
                            @elseif (in_array($order['status_raw'], ['rejected', 'cancelled', 'failed']))
                                <span class="dash-badge-offline">{{ $order['status'] }}</span>
                            @else
                                <span class="dash-badge-pending">{{ $order['status'] }}</span>
                            @endif
                        </td>
                        <td>{{ $order['date'] }}</td>
                        <td><a href="{{ route('dashboard.orders.show', $order['order_id']) }}" class="ui-btn-ghost text-xs">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-ink-muted">No orders yet. <a href="{{ route('dashboard.shop') }}" class="text-accent hover:underline">Browse plans</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
