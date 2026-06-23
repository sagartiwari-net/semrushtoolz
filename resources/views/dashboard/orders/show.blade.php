@extends('layouts.dashboard')

@section('title', 'Order ' . $order->order_number)

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.orders') }}" class="text-sm text-ink-muted hover:text-accent">&larr; All Orders</a>
        <h1 class="dash-page-title mt-2">{{ $order->order_number }}</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="dash-card lg:col-span-2 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-ink">{{ $order->purchasedItemName() }}</h2>
                    <p class="text-sm text-ink-muted">{{ $order->duration_months }} month(s) · {{ strtoupper($order->currency) }}</p>
                </div>
                <span @class([
                    'dash-badge-online' => $order->status === 'completed',
                    'dash-badge-pending' => $order->status !== 'completed',
                ])>{{ $statusLabel }}</span>
            </div>

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-muted">Order date</dt><dd class="font-medium text-ink">{{ $order->created_at->format('M d, Y H:i') }}</dd></div>
                <div><dt class="text-ink-muted">Payment method</dt><dd class="font-medium text-ink">{{ $paymentMethodLabel }}</dd></div>
                <div><dt class="text-ink-muted">Subtotal</dt><dd class="text-ink">{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->subtotal, 0) }}</dd></div>
                @if ($order->coupon_code)
                    <div><dt class="text-ink-muted">Coupon</dt><dd class="font-mono text-ink">{{ $order->coupon_code }}</dd></div>
                @endif
                @if ($order->discount > 0)
                    <div><dt class="text-ink-muted">Discount</dt><dd class="text-success">-{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->discount, 0) }}</dd></div>
                @endif
                @if ($order->hasGst())
                    <div><dt class="text-ink-muted">Taxable amount</dt><dd class="text-ink">{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->amountBeforeGst(), 2) }}</dd></div>
                    <div><dt class="text-ink-muted">{{ \App\Models\SiteSetting::gstConfig()['label'] }} ({{ rtrim(rtrim(number_format((float) $order->gst_rate, 2), '0'), '.') }}%)</dt><dd class="text-ink">{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->gst_amount, 2) }}</dd></div>
                @endif
                <div><dt class="text-ink-muted">Total</dt><dd class="text-lg font-bold text-accent">{{ $formattedTotal }}</dd></div>
                @if ((float) $order->wallet_amount_used > 0)
                    <div><dt class="text-ink-muted">Paid from wallet</dt><dd class="text-ink">₹{{ number_format($order->wallet_amount_used, 2) }}</dd></div>
                @endif
                @if ((float) $order->wallet_cashback_amount > 0)
                    <div><dt class="text-ink-muted">Wallet cashback</dt><dd class="text-success">+₹{{ number_format($order->wallet_cashback_amount, 2) }}</dd></div>
                @endif
                @if ($order->paid_at)
                    <div><dt class="text-ink-muted">Paid at</dt><dd class="font-medium text-ink">{{ $order->paid_at->format('M d, Y H:i') }}</dd></div>
                @endif
                @if ($order->expires_at && ! $order->paid_at)
                    <div><dt class="text-ink-muted">Expires</dt><dd class="font-medium text-warning">{{ $order->expires_at->format('M d, Y H:i') }}</dd></div>
                @endif
            </dl>

            @if ($order->admin_note && in_array($order->status, ['rejected', 'cancelled']))
                <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                    <strong>Note:</strong> {{ $order->admin_note }}
                </div>
            @endif

            @if ($order->status === 'completed')
                <div class="flex flex-wrap gap-3">
                    @if ($order->isWalletTopup())
                        <a href="{{ route('dashboard.wallet') }}" class="ui-btn-primary">Go to Wallet</a>
                    @else
                        <a href="{{ route('dashboard.tools') }}" class="ui-btn-primary">Go to My Tools</a>
                    @endif
                    <a href="{{ route('dashboard.orders.invoice', $order) }}" class="ui-btn-outline">Download Invoice (PDF)</a>
                </div>
            @elseif ($order->status === 'refunded')
                <a href="{{ route('dashboard.orders.invoice', $order) }}" class="ui-btn-outline">Download Invoice (PDF)</a>
            @elseif (in_array($order->status, ['pending', 'awaiting_payment', 'awaiting_proof', 'verifying']))
                @php
                    $payRoute = match ($order->payment_method) {
                        'upi' => route('dashboard.orders.pay.upi', $order),
                        'offline' => route('dashboard.orders.pay.offline', $order),
                        default => route('dashboard.orders.pay.paypal', $order),
                    };
                @endphp
                <a href="{{ $payRoute }}" class="ui-btn-primary">Continue Payment</a>
            @endif
        </div>

        <div class="dash-card">
            <h3 class="text-sm font-bold text-ink">Payment Proof</h3>
            @if ($order->payment_proof)
                <a href="{{ Storage::url($order->payment_proof) }}" target="_blank" class="mt-3 block overflow-hidden rounded-xl border border-line">
                    <img src="{{ Storage::url($order->payment_proof) }}" alt="Payment proof" class="w-full">
                </a>
            @else
                <p class="mt-2 text-sm text-ink-muted">No proof uploaded yet.</p>
            @endif
        </div>
    </div>
@endsection
