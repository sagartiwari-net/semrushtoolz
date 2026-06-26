@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.orders') }}" class="text-sm text-ink-muted hover:text-accent">&larr; All Orders</a>
            <h1 class="dash-page-title mt-1">{{ $order->order_number }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if (in_array($order->status, ['completed', 'refunded']))
                <a href="{{ route('admin.orders.invoice', $order) }}" class="ui-btn-outline text-sm">Download Invoice</a>
            @endif
            <span @class([
            'dash-badge-online' => $order->status === 'completed',
            'dash-badge-pending' => $order->status !== 'completed',
        ])>{{ $statusLabel }}</span>
        </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="dash-card lg:col-span-2 space-y-5">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-muted">User</dt><dd class="font-medium text-ink">{{ $order->user->name }} ({{ $order->user->email }})</dd></div>
                <div><dt class="text-ink-muted">Item</dt><dd class="font-medium text-ink">{{ $order->purchasedItemName() }}</dd></div>
                <div><dt class="text-ink-muted">Order type</dt><dd>{{ $order->isWalletTopup() ? 'Wallet top-up' : 'Subscription' }}</dd></div>
                <div><dt class="text-ink-muted">Duration</dt><dd>{{ $order->duration_months }} month(s)</dd></div>
                <div><dt class="text-ink-muted">Payment</dt><dd>{{ $paymentMethodLabel }}</dd></div>
                <div><dt class="text-ink-muted">Subtotal</dt><dd>{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->subtotal, 0) }}</dd></div>
                @if ($order->hasGst())
                    <div><dt class="text-ink-muted">Taxable amount</dt><dd>{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->amountBeforeGst(), 2) }}</dd></div>
                    <div><dt class="text-ink-muted">{{ \App\Models\SiteSetting::gstConfig()['label'] }} ({{ rtrim(rtrim(number_format((float) $order->gst_rate, 2), '0'), '.') }}%)</dt><dd>{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->gst_amount, 2) }}</dd></div>
                @endif
                <div><dt class="text-ink-muted">Total</dt><dd class="text-lg font-bold text-accent">{{ $formattedTotal }}</dd></div>
                @if ((float) $order->wallet_amount_used > 0)
                    <div><dt class="text-ink-muted">Wallet used</dt><dd>₹{{ number_format($order->wallet_amount_used, 2) }}</dd></div>
                @endif
                @if ((float) $order->wallet_cashback_amount > 0)
                    <div><dt class="text-ink-muted">Cashback awarded</dt><dd class="text-success">₹{{ number_format($order->wallet_cashback_amount, 2) }}</dd></div>
                @endif
                <div><dt class="text-ink-muted">Created</dt><dd>{{ $order->created_at->format('M d, Y H:i') }}</dd></div>
                @if ($order->paid_at)
                    <div><dt class="text-ink-muted">Paid</dt><dd>{{ $order->paid_at->format('M d, Y H:i') }}</dd></div>
                @endif
            </dl>

            @if ($order->payment_note)
                <div class="rounded-xl border border-line bg-surface px-4 py-3 text-sm">
                    <strong>Customer message:</strong>
                    <p class="mt-2 whitespace-pre-wrap text-ink-secondary">{{ $order->payment_note }}</p>
                </div>
            @endif

            @if ($order->payment_proof)
                <div>
                    <h3 class="text-sm font-bold text-ink">Payment Proof</h3>
                    <a href="{{ Storage::url($order->payment_proof) }}" target="_blank" class="mt-2 block max-w-md overflow-hidden rounded-xl border border-line">
                        <img src="{{ Storage::url($order->payment_proof) }}" alt="Proof" class="w-full">
                    </a>
                </div>
            @endif

            @if ($order->admin_note)
                <div class="rounded-xl border border-line bg-surface px-4 py-3 text-sm">
                    <strong>Admin note:</strong> {{ $order->admin_note }}
                </div>
            @endif
        </div>

        @if ($order->status !== 'completed')
            <div class="space-y-4">
                <form method="POST" action="{{ route('admin.orders.approve', $order) }}" class="dash-card space-y-3">
                    @csrf
                    <h3 class="font-bold text-success">Approve Order</h3>
                    <p class="text-sm text-ink-muted">Activates subscription for this user.</p>
                    <textarea name="admin_note" class="ui-input" rows="2" placeholder="Optional note"></textarea>
                    <button type="submit" class="ui-btn-primary w-full bg-success hover:bg-success/90">Approve &amp; Activate</button>
                </form>

                <form method="POST" action="{{ route('admin.orders.reject', $order) }}" class="dash-card space-y-3">
                    @csrf
                    <h3 class="font-bold text-danger">Reject Order</h3>
                    <textarea name="reason" class="ui-input" rows="2" placeholder="Reason for rejection" required></textarea>
                    <button type="submit" class="ui-btn-outline w-full border-danger text-danger hover:bg-danger/10">Reject Order</button>
                </form>
            </div>
        @endif
    </div>

    @if ($order->status === 'completed' && $order->isSubscription())
        <div class="mt-6 grid max-w-2xl gap-4 sm:grid-cols-2">
            @if ($linkedSubscription)
                <form method="POST" action="{{ route('admin.orders.revoke-access', $order) }}" class="dash-card space-y-3 border-warning/30">
                    @csrf
                    <h3 class="font-bold text-warning">Cancel plan (no refund)</h3>
                    <p class="text-sm text-ink-muted">Ends subscription access immediately. Order stays completed — no money is returned.</p>
                    <textarea name="note" class="ui-input" rows="2" placeholder="Optional note"></textarea>
                    <button type="submit" class="ui-btn-outline w-full border-warning text-warning hover:bg-warning/10" onclick="return confirm('Cancel plan without refund?')">Revoke access</button>
                </form>
            @else
                <div class="dash-card border-line/50 text-sm text-ink-muted">
                    <h3 class="font-bold text-ink">Subscription</h3>
                    <p class="mt-2">No active subscription linked to this order (already ended or expired).</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="dash-card space-y-3 border-danger/30">
                @csrf
                <h3 class="font-bold text-danger">Refund order</h3>
                <p class="text-sm text-ink-muted">Returns payment (wallet credit if applicable), ends subscription, and reverses affiliate commission.</p>
                <textarea name="reason" class="ui-input" rows="2" placeholder="Refund reason" required></textarea>
                <button type="submit" class="ui-btn-outline w-full border-danger text-danger hover:bg-danger/10" onclick="return confirm('Refund this order?')">Process refund</button>
            </form>
        </div>
    @endif
@endsection
