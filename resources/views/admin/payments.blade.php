@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
  @php
      $upiCount = $pendingUpi->count();
      $offlineCount = $pendingOffline->count();
  @endphp

    <div class="grid gap-4 lg:grid-cols-3">
        @php
            $paypalOk = app(\App\Services\PayPalService::class)->isConfigured();
        @endphp
        <div class="dash-card">
            <h3 class="text-base font-bold text-ink">PayPal Recurring</h3>
            <p class="mt-1 text-sm text-ink-muted">USD monthly subscriptions</p>
            <span @class(['mt-3', 'dash-badge-online' => $paypalOk, 'dash-badge-pending' => ! $paypalOk])>
                {{ $paypalOk ? 'Configured' : 'Not configured' }}
            </span>
            <p class="mt-2 text-xs text-ink-muted">
                <a href="{{ route('admin.payment-integration.edit') }}#paypal" class="text-accent hover:underline">Payment Integration → PayPal</a>
            </p>
        </div>
        <div class="dash-card">
            <h3 class="text-base font-bold text-ink">UPI (Buyahref Hub)</h3>
            <p class="mt-1 text-sm text-ink-muted">{{ $upiCount }} pending</p>
            @php
                $bh = \App\Models\SiteSetting::buyahrefConfig();
                $buyahrefOk = $bh['enabled'] && filled($bh['api_key']) && \App\Models\SiteSetting::hasBuyahrefSecret();
            @endphp
            <span @class(['dash-badge-online' => $buyahrefOk, 'dash-badge-pending' => ! $buyahrefOk])>
                {{ $buyahrefOk ? 'Configured' : 'Not configured' }}
            </span>
            <p class="mt-2 text-xs text-ink-muted">
                <a href="{{ route('admin.payment-integration.edit') }}" class="text-accent hover:underline">Payment Integration settings →</a>
            </p>
        </div>
        <div class="dash-card">
            <h3 class="text-base font-bold text-ink">Offline Payment</h3>
            <p class="mt-1 text-sm text-ink-muted">{{ $offlineCount }} proofs to review</p>
            <span class="dash-badge-pending mt-3">Manual review</span>
        </div>
    </div>

    @php $paymentsTab = request()->query('tab', 'upi'); @endphp

    <x-dash-tabs :tabs="['upi' => 'Pending UPI ('.$upiCount.')', 'offline' => 'Offline Proofs ('.$offlineCount.')']" :active="$paymentsTab">
        @if ($paymentsTab === 'upi')
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>Order</th><th>User</th><th>Amount</th><th>Status</th><th>Waiting</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($pendingUpi as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>{{ $orderService->formatAmount($order) }}</td>
                                <td>{{ $orderService->statusLabel($order->status) }}</td>
                                <td>{{ $order->created_at->diffForHumans() }}</td>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="ui-btn-primary text-xs">Review</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-ink-muted">No pending UPI orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>Order</th><th>User</th><th>Amount</th><th>Status</th><th>Proof</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($pendingOffline as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>{{ $orderService->formatAmount($order) }}</td>
                                <td>{{ $orderService->statusLabel($order->status) }}</td>
                                <td>{{ $order->payment_proof ? 'Uploaded' : '—' }}</td>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="ui-btn-primary text-xs">Review</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-ink-muted">No pending offline orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-dash-tabs>
@endsection
