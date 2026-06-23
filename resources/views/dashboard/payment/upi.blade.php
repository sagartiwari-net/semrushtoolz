@extends('layouts.dashboard')

@section('title', 'Pay via UPI')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.orders.show', $order) }}" class="text-sm text-ink-muted hover:text-accent">&larr; Order {{ $order->order_number }}</a>
        <h1 class="dash-page-title mt-2">Pay via UPI</h1>
    </div>

    @if ($order->status === 'completed')
        <div class="dash-card text-center">
            <div class="text-4xl">✅</div>
            <h2 class="mt-3 text-xl font-bold text-success">Payment Verified!</h2>
            <p class="mt-2 text-ink-muted">Your subscription is now active.</p>
            <a href="{{ route('dashboard.tools') }}" class="ui-btn-primary mt-5">Access My Tools</a>
        </div>
    @elseif ($order->status === 'cancelled')
        <div class="dash-card text-center">
            <h2 class="text-xl font-bold text-danger">Payment Failed</h2>
            <p class="mt-2 text-ink-muted">This order was not paid within {{ $expiryMinutes }} minutes.</p>
            <a href="{{ route('dashboard.shop') }}" class="ui-btn-primary mt-5">Try Again</a>
        </div>
    @elseif (!($buyahrefEnabled ?? false))
        <div class="dash-card text-center">
            <h2 class="text-xl font-bold text-warning">UPI payments not configured</h2>
            <p class="mt-2 text-ink-muted">Ask admin to configure <strong>Admin → Payment Integration</strong>.</p>
            <a href="{{ route('dashboard.orders.show', $order) }}" class="ui-btn-primary mt-5">Back to Order</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="dash-card space-y-5">
                <div class="rounded-xl bg-surface p-5 text-center">
                    <p class="text-sm text-ink-muted">Pay exactly</p>
                    <p class="mt-1 text-4xl font-extrabold text-accent">{{ $formattedTotal }}</p>
                    <p class="mt-2 text-sm text-ink-muted">to</p>
                    <p class="mt-1 font-mono text-lg font-bold text-ink">{{ $upi['id'] }}</p>
                    <p class="text-sm text-ink-muted">{{ $upi['name'] }}</p>
                </div>

                <div class="rounded-xl border border-line p-4 text-sm">
                    <h3 class="font-bold text-ink">Instructions</h3>
                    <ol class="mt-2 list-inside list-decimal space-y-1 text-ink-secondary">
                        <li>Open your UPI app (GPay, PhonePe, Paytm)</li>
                        <li>Pay <strong>{{ $formattedTotal }}</strong> to the UPI ID above</li>
                        <li>Add order ref <strong>{{ $order->order_number }}</strong> in the note</li>
                        <li>Payment auto-verifies within 1–2 minutes</li>
                    </ol>
                </div>

                @if ($order->expires_at)
                    <div class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning" data-upi-expiry data-expires="{{ $order->expires_at->timestamp }}">
                        Complete payment before <strong>{{ $order->expires_at->format('H:i') }}</strong>
                        (<span data-countdown>--:--</span> remaining)
                    </div>
                @endif
            </div>

            <div class="dash-card">
                <h3 class="font-bold text-ink">Order Details</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-muted">Order</dt><dd class="font-mono font-medium">{{ $order->order_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Item</dt><dd>{{ $order->purchasedItemName() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Amount</dt><dd class="font-bold text-accent">{{ $formattedTotal }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Status</dt><dd id="upi-status">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</dd></div>
                </dl>

                <div class="mt-6 flex items-center gap-3 rounded-xl bg-surface p-4 text-sm text-ink-secondary">
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-accent border-t-transparent" data-poll-spinner></div>
                    <span>Waiting for payment confirmation…</span>
                </div>
            </div>
        </div>

        <script>
            window.UPI_POLL_URL = @json(route('dashboard.orders.status', $order));
        </script>
    @endif
@endsection
