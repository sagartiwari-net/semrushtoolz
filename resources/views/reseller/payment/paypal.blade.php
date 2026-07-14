@extends('layouts.reseller')

@section('title', 'Pay via PayPal')

@section('content')
    <div class="mb-5">
        <a href="{{ route('reseller.balance.index') }}" class="text-sm text-accent hover:underline">← Balance</a>
        <h1 class="dash-page-title mt-2">Pay via PayPal</h1>
        <p class="text-sm text-ink-secondary">One-time USD charge. ₹{{ number_format($creditInr, 2) }} will be credited to your reseller balance.</p>
    </div>

    @if ($order->status === 'completed')
        <div class="ui-card p-6 text-center">
            <p class="text-lg font-bold text-success">Payment completed</p>
            <a href="{{ route('reseller.balance.index') }}" class="ui-btn-primary mt-4 inline-flex">Back to balance</a>
        </div>
    @elseif ($order->status === 'cancelled')
        <div class="ui-card p-6 text-center">
            <p class="text-lg font-bold text-danger">Order expired</p>
            <a href="{{ route('reseller.balance.index') }}" class="ui-btn-primary mt-4 inline-flex">Try again</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="ui-card space-y-4 p-5">
                <div class="rounded-xl bg-surface-2 p-5 text-center">
                    <p class="text-sm text-ink-muted">PayPal charge (USD)</p>
                    <p class="mt-1 text-4xl font-extrabold text-ink">${{ number_format($chargeUsd ?? $order->total, 2) }}</p>
                    <p class="mt-2 text-xs text-ink-muted">Credits ₹{{ number_format($creditInr, 2) }} to your balance (one-time)</p>
                </div>

                @if (($paypal['client_id'] ?? null) && $paypalPlanId)
                    <div id="paypal-button-container" class="min-h-[45px]"></div>
                    <p id="paypal-error" class="hidden rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger"></p>
                @else
                    <div class="rounded-xl border border-line p-5 text-center text-sm text-ink-muted">
                        PayPal is not configured. Admin → Payment Integration → PayPal.
                    </div>
                @endif

                @if ($order->expires_at)
                    <p class="text-center text-sm text-warning">Complete before {{ $order->expires_at->format('M j, H:i') }}</p>
                @endif
            </div>

            <div class="ui-card p-5">
                <h3 class="font-bold text-ink">Top-up details</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-muted">Order</dt><dd class="font-mono">{{ $order->order_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Credit (INR)</dt><dd>₹{{ number_format($creditInr, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Pay (USD)</dt><dd>${{ number_format($chargeUsd ?? $order->total, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Status</dt><dd>{{ ucfirst($order->status) }}</dd></div>
                </dl>
            </div>
        </div>
    @endif
@endsection

@if (($paypal['client_id'] ?? null) && $paypalPlanId && $order->status !== 'completed' && $order->status !== 'cancelled')
    @push('scripts')
        <script src="https://www.paypal.com/sdk/js?client-id={{ $paypal['client_id'] }}&vault=true&intent=subscription" data-sdk-integration-source="button-factory"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const errorEl = document.getElementById('paypal-error');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                if (!window.paypal) {
                    errorEl.textContent = 'PayPal SDK failed to load.';
                    errorEl.classList.remove('hidden');
                    return;
                }

                paypal.Buttons({
                    style: { shape: 'rect', color: 'gold', layout: 'vertical', label: 'paypal' },
                    createSubscription: function (data, actions) {
                        return actions.subscription.create({
                            plan_id: @json($paypalPlanId),
                            custom_id: @json($order->order_number),
                            application_context: {
                                brand_name: 'Semrushtoolz',
                                shipping_preference: 'NO_SHIPPING',
                                user_action: 'SUBSCRIBE_NOW'
                            }
                        });
                    },
                    onApprove: function (data) {
                        return fetch(@json($approveUrl), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ subscription_id: data.subscriptionID })
                        })
                        .then(res => res.json().then(body => ({ ok: res.ok, body })))
                        .then(({ ok, body }) => {
                            if (!ok) throw new Error(body.message || 'Payment approval failed.');
                            window.location.href = body.redirect || @json(route('reseller.balance.index'));
                        })
                        .catch(err => {
                            errorEl.textContent = err.message;
                            errorEl.classList.remove('hidden');
                        });
                    },
                    onError: function (err) {
                        errorEl.textContent = err?.message || 'PayPal encountered an error.';
                        errorEl.classList.remove('hidden');
                    }
                }).render('#paypal-button-container');
            });
        </script>
    @endpush
@endif
