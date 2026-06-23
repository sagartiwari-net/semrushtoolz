@extends('layouts.dashboard')

@section('title', 'Pay via PayPal')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.orders.show', $order) }}" class="text-sm text-ink-muted hover:text-accent">&larr; Order {{ $order->order_number }}</a>
        <h1 class="dash-page-title mt-2">Pay via PayPal</h1>
        <p class="mt-1 text-sm text-ink-muted">USD recurring subscription — billed monthly on PayPal.</p>
    </div>

    @if ($order->status === 'completed')
        <div class="dash-card text-center">
            <div class="text-4xl">✅</div>
            <h2 class="mt-3 text-xl font-bold text-success">Subscription Active!</h2>
            <p class="mt-2 text-sm text-ink-muted">PayPal will auto-renew each month until you cancel.</p>
            <a href="{{ route('dashboard.tools') }}" class="ui-btn-primary mt-5">Access My Tools</a>
        </div>
    @elseif ($order->status === 'cancelled')
        <div class="dash-card text-center">
            <h2 class="text-xl font-bold text-danger">Order Expired</h2>
            <a href="{{ route('dashboard.shop') }}" class="ui-btn-primary mt-5">Try Again</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="dash-card space-y-5">
                <div class="rounded-xl bg-surface p-5 text-center">
                    <p class="text-sm text-ink-muted">Monthly charge (USD)</p>
                    <p class="mt-1 text-4xl font-extrabold text-accent">${{ number_format($monthlyUsd ?? $order->total, 2) }}</p>
                    @if ($recurringNote)
                        <p class="mt-2 text-xs text-ink-muted">{{ $recurringNote }}</p>
                    @endif
                </div>

                @if ($paypal['client_id'] && $paypalPlanId)
                    <div id="paypal-button-container" class="min-h-[45px]"></div>
                    <p id="paypal-error" class="hidden rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger"></p>
                @else
                    <div class="rounded-xl border border-line p-5 text-center">
                        <p class="text-sm text-ink-muted">PayPal integration is being configured.</p>
                        <p class="mt-2 text-sm text-ink-secondary">Admin → <strong>Payment Integration</strong> → PayPal section mein credentials save karo.</p>
                    </div>
                @endif

                @if ($order->expires_at)
                    <p class="text-center text-sm text-warning">
                        Complete payment before {{ $order->expires_at->format('M d, H:i') }}
                    </p>
                @endif
            </div>

            <div class="dash-card">
                <h3 class="font-bold text-ink">Order Details</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-muted">Order</dt><dd class="font-mono font-medium">{{ $order->order_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Item</dt><dd>{{ $order->purchasedItemName() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Billing</dt><dd>Monthly recurring (USD)</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Status</dt><dd>{{ ucfirst($order->status) }}</dd></div>
                </dl>

                <div class="mt-5 rounded-xl border border-line bg-surface p-4 text-xs text-ink-muted">
                    You approve a PayPal subscription. We charge the USD monthly rate only — not INR.
                    Cancel anytime from your PayPal account.
                </div>

                <a href="{{ route('dashboard.orders') }}" class="ui-btn-outline mt-6 w-full justify-center">View All Orders</a>
            </div>
        </div>
    @endif
@endsection

@if ($paypal['client_id'] && $paypalPlanId && $order->status !== 'completed' && $order->status !== 'cancelled')
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
                    style: {
                        shape: 'pill',
                        color: 'gold',
                        layout: 'vertical',
                        label: 'subscribe'
                    },
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
                            if (!ok) {
                                throw new Error(body.message || 'Payment approval failed.');
                            }
                            window.location.href = body.redirect || @json(route('dashboard.tools'));
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
