@extends('layouts.admin')

@section('title', 'Payment Integration')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Payment Integration</h1>
        <p class="text-sm text-ink-secondary">Manage UPI (Buyahref Hub) and PayPal recurring credentials.</p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-2">
        {{-- UPI --}}
        <div class="space-y-5">
            <div>
                <h2 class="text-lg font-bold text-ink">UPI (Buyahref Hub)</h2>
                <p class="text-sm text-ink-secondary">Auto-verified QR payments for INR checkout.</p>
            </div>

            @if ($config['enabled'] && ! $upiReady)
                <div class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning">
                    <strong>UPI checkout is not active yet.</strong>
                    @if (! $hasSecret)
                        Merchant API Secret is not saved — paste your <strong>sk_...</strong> key below and save. UPI will not appear at checkout without it.
                    @else
                        Check your Hub URL or API Key.
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('admin.payment-integration.update') }}" class="dash-card space-y-5">
                @csrf
                @method('PUT')

                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="enabled" value="1" class="rounded" @checked(old('enabled', $config['enabled']))>
                    <span><strong>Enable UPI payments</strong> — when off, UPI option hidden at checkout</span>
                </label>

                <div>
                    <label class="ui-label">Payment name (shown at checkout)</label>
                    <input class="ui-input" name="display_name" value="{{ old('display_name', $config['display_name']) }}" required placeholder="UPI / Pay via UPI / PhonePe & GPay">
                    <p class="mt-1 text-xs text-ink-muted">Label shown for this payment method on the checkout page.</p>
                </div>

                <div>
                    <label class="ui-label">Payment description (optional)</label>
                    <input class="ui-input" name="display_description" value="{{ old('display_description', $config['display_description']) }}" placeholder="Secure QR — verified in 30 seconds">
                    <p class="mt-1 text-xs text-ink-muted">Short description shown below the option at checkout.</p>
                </div>

                <hr class="border-line">

                <p class="text-sm font-semibold text-ink">Buyahref Payment Hub credentials</p>

                <div>
                    <label class="ui-label">Hub URL (public)</label>
                    <input class="ui-input font-mono text-sm" name="hub_url" value="{{ old('hub_url', $config['hub_url']) }}" required placeholder="https://buyahref.com/payment">
                    <p class="mt-1 text-xs text-ink-muted">Checkout redirect ke liye — customers is URL par jaate hain.</p>
                </div>

                <div>
                    <label class="ui-label">Internal Hub URL (same server — recommended)</label>
                    <input class="ui-input font-mono text-sm" name="hub_internal_url" value="{{ old('hub_internal_url', $config['hub_internal_url'] ?? '') }}" placeholder="http://127.0.0.1:8090">
                    <p class="mt-1 text-xs text-ink-muted">Optional. Sirf tab use karo jab SemrushToolz aur Payment Hub <strong>same server</strong> par hon. Alag server par <strong>khali chhodo</strong> — public URL use hoga.</p>
                </div>

                <div>
                    <label class="ui-label">Merchant API Key</label>
                    <input class="ui-input font-mono text-sm" name="api_key" value="{{ old('api_key', $config['api_key']) }}" required placeholder="mk_semrushtoolz_001">
                    <p class="mt-1 text-xs text-ink-muted">From <a href="https://buyahref.com/payment/admin/merchants" class="text-accent hover:underline" target="_blank" rel="noopener">Payment Hub → Merchants</a></p>
                </div>

                <div>
                    <label class="ui-label">Merchant API Secret @unless($hasSecret)<span class="text-danger">*</span>@endunless</label>
                    <input class="ui-input font-mono text-sm" type="password" name="api_secret" value="" placeholder="{{ $hasSecret ? '•••••••• (leave blank to keep current)' : 'sk_... paste from Payment Hub' }}" autocomplete="new-password" @unless($hasSecret) required @endunless>
                    @error('api_secret')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                    @if ($hasSecret)
                        <p class="mt-1 text-xs text-success">Secret saved. Leave blank unless you want to replace it.</p>
                    @else
                        <p class="mt-1 text-xs text-ink-muted">Payment Hub → Merchants → Regenerate Secret → copy <strong>sk_...</strong> and paste here.</p>
                    @endif
                </div>

                <div>
                    <label class="ui-label">UPI payment window (minutes)</label>
                    <input class="ui-input w-32" type="number" name="order_expiry_minutes" min="1" max="60" value="{{ old('order_expiry_minutes', $config['order_expiry_minutes']) }}" required>
                    <p class="mt-1 text-xs text-ink-muted">Customer must pay within this time. Hub default is 5 minutes — keep in sync.</p>
                </div>

                <div class="rounded-xl bg-surface p-4 text-sm">
                    <p class="font-semibold text-ink">Webhook URL (copy to Payment Hub merchant)</p>
                    <p class="mt-2 break-all font-mono text-xs text-ink-secondary">{{ $webhookUrl }}</p>
                    <p class="mt-2 text-xs text-ink-muted">Admin → Merchants → your site → paste the Webhook URL.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="ui-btn-primary">Save UPI Settings</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.payment-integration.buyahref.test') }}">
                @csrf
                <button type="submit" class="ui-btn-outline text-sm">Test UPI Connection</button>
            </form>
        </div>

        {{-- PayPal --}}
        <div class="space-y-5" id="paypal">
            <div>
                <h2 class="text-lg font-bold text-ink">PayPal Recurring (USD)</h2>
                <p class="text-sm text-ink-secondary">Monthly auto-renew subscriptions. Test in Sandbox, then switch to Live mode.</p>
            </div>

            @if ($paypal['enabled'] && ! $paypalReady)
                <div class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning">
                    <strong>PayPal checkout is not active yet.</strong>
                    @if (! $hasPayPalSecret)
                        Client Secret is not saved — paste it below and save.
                    @else
                        Check your Client ID or run Test Connection.
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('admin.payment-integration.paypal') }}" class="dash-card space-y-5">
                @csrf
                @method('PUT')

                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="enabled" value="1" class="rounded" @checked(old('enabled', $paypal['enabled']))>
                    <span><strong>Enable PayPal</strong> — monthly recurring subscription on USD checkout</span>
                </label>

                <div>
                    <label class="ui-label">Mode</label>
                    <select class="ui-input w-auto" name="mode">
                        <option value="sandbox" @selected(old('mode', $paypal['mode']) === 'sandbox')>Sandbox (testing)</option>
                        <option value="live" @selected(old('mode', $paypal['mode']) === 'live')>Live (production)</option>
                    </select>
                    <p class="mt-1 text-xs text-ink-muted">Sandbox uses separate credentials — use a Sandbox app in the Developer Dashboard.</p>
                </div>

                <div>
                    <label class="ui-label">Client ID</label>
                    <input class="ui-input font-mono text-sm" name="client_id" value="{{ old('client_id', $paypal['client_id']) }}" required placeholder="AeA...">
                    <p class="mt-1 text-xs text-ink-muted">From <a href="https://developer.paypal.com/dashboard/applications/live" class="text-accent hover:underline" target="_blank" rel="noopener">PayPal Developer → Apps</a></p>
                </div>

                <div>
                    <label class="ui-label">Client Secret @unless($hasPayPalSecret)<span class="text-danger">*</span>@endunless</label>
                    <input class="ui-input font-mono text-sm" type="password" name="client_secret" value="" placeholder="{{ $hasPayPalSecret ? '•••••••• (leave blank to keep current)' : 'Secret from PayPal app' }}" autocomplete="new-password" @unless($hasPayPalSecret) required @endunless>
                    @error('client_secret')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="ui-label">Webhook ID (recommended for renewals)</label>
                    <input class="ui-input font-mono text-sm" name="webhook_id" value="{{ old('webhook_id', $paypal['webhook_id']) }}" placeholder="WH-...">
                    <p class="mt-1 text-xs text-ink-muted">PayPal Developer → Webhooks → create webhook → copy ID.</p>
                </div>

                <div class="rounded-xl bg-surface p-4 text-sm">
                    <p class="font-semibold text-ink">Webhook URL (paste in PayPal Developer)</p>
                    <p class="mt-2 break-all font-mono text-xs text-ink-secondary">{{ $paypalWebhookUrl }}</p>
                    <p class="mt-2 text-xs text-ink-muted">Events: <code>BILLING.SUBSCRIPTION.ACTIVATED</code>, <code>BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED</code>, <code>BILLING.SUBSCRIPTION.CANCELLED</code>, <code>BILLING.SUBSCRIPTION.EXPIRED</code>, <code>PAYMENT.SALE.REFUNDED</code>, <code>PAYMENT.CAPTURE.REFUNDED</code></p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="ui-btn-primary">Save PayPal Settings</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.payment-integration.paypal.test') }}">
                @csrf
                <button type="submit" class="ui-btn-outline text-sm">Test PayPal Connection</button>
            </form>
        </div>
    </div>
@endsection
