@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.settings.general') }}" class="dash-card space-y-4">
            @csrf
            @method('PUT')
            <h3 class="dash-card-title">General</h3>
            <div>
                <label class="ui-label">Site name</label>
                <input class="ui-input" name="site_name" value="{{ old('site_name', $general['site_name']) }}" required>
            </div>
            <div>
                <label class="ui-label">Favicon URL</label>
                <input class="ui-input font-mono text-sm" type="url" name="site_favicon_url" value="{{ old('site_favicon_url', $general['favicon_url']) }}" placeholder="https://example.com/favicon.png">
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ old('site_favicon_url', $general['favicon_url']) }}" alt="Favicon preview" class="h-10 w-10 rounded-full border border-line bg-ink object-cover">
                    <p class="text-xs text-ink-muted">PNG recommended, square (e.g. 512×512). Shown in browser tab, bookmarks, and emails.</p>
                </div>
            </div>
            <div>
                <label class="ui-label">Support email</label>
                <input class="ui-input" type="email" name="support_email" value="{{ old('support_email', $general['support_email']) }}" required>
            </div>
            <div>
                <label class="ui-label">WhatsApp number</label>
                <input class="ui-input" name="whatsapp_number" value="{{ old('whatsapp_number', $general['whatsapp_number']) }}" placeholder="+91 98765 43210">
            </div>
            <div>
                <label class="ui-label">Telegram handle</label>
                <input class="ui-input" name="telegram_handle" value="{{ old('telegram_handle', $general['telegram_handle']) }}" placeholder="@semrushtoolz">
            </div>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="maintenance_mode" value="1" class="rounded" @checked(old('maintenance_mode', $general['maintenance_mode']))>
                <span>Maintenance mode</span>
            </label>

            <div class="border-t border-line pt-4 space-y-3">
                <p class="text-sm font-semibold text-ink">GST (India — INR orders only)</p>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="gst_enabled" value="1" class="rounded" @checked(old('gst_enabled', $general['gst_enabled']))>
                    <span>Enable GST on INR checkout &amp; invoices</span>
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="ui-label">GST rate %</label>
                        <input class="ui-input" type="number" name="gst_rate" min="0" max="100" step="0.01" value="{{ old('gst_rate', $general['gst_rate']) }}">
                    </div>
                    <div>
                        <label class="ui-label">Tax label</label>
                        <input class="ui-input" name="gst_label" value="{{ old('gst_label', $general['gst_label']) }}" placeholder="GST">
                    </div>
                </div>
                <div>
                    <label class="ui-label">GSTIN (optional, shown on invoice)</label>
                    <input class="ui-input font-mono" name="gst_number" value="{{ old('gst_number', $general['gst_number']) }}" placeholder="22AAAAA0000A1Z5">
                </div>
                <p class="text-xs text-ink-muted">When disabled, no tax is added to payments or invoices. When enabled, GST is calculated on the discounted INR total at checkout.</p>
            </div>

            <button type="submit" class="ui-btn-primary">Save General</button>
        </form>

        <form method="POST" action="{{ route('admin.settings.affiliate') }}" class="dash-card space-y-4">
            @csrf
            @method('PUT')
            <h3 class="dash-card-title">Affiliate</h3>
            <div>
                <label class="ui-label">Commission %</label>
                <input class="ui-input" type="number" name="commission_rate" min="0" max="100" step="0.1" value="{{ old('commission_rate', $affiliate['commission_rate'] * 100) }}" required>
            </div>
            <div>
                <label class="ui-label">Min payout threshold (₹)</label>
                <input class="ui-input" type="number" name="min_payout" min="1" value="{{ old('min_payout', $affiliate['min_payout']) }}" required>
            </div>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="auto_approve" value="1" class="rounded" @checked(old('auto_approve', $affiliate['auto_approve']))>
                <span>Auto-approve commissions on payment</span>
            </label>
            <div class="border-t border-line pt-4">
                <p class="mb-3 text-sm font-semibold text-ink">Referral signup bonus (new users)</p>
                <label class="mb-3 flex items-center gap-3 text-sm">
                    <input type="checkbox" name="signup_bonus_enabled" value="1" class="rounded" @checked(old('signup_bonus_enabled', $affiliate['signup_bonus_enabled']))>
                    <span>Enable first-month discount for referred signups</span>
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="ui-label">Bonus discount %</label>
                        <input class="ui-input" type="number" name="signup_bonus_percent" min="0" max="100" step="0.1" value="{{ old('signup_bonus_percent', $affiliate['signup_bonus_percent'] * 100) }}">
                    </div>
                    <div>
                        <label class="ui-label">Valid for (days)</label>
                        <input class="ui-input" type="number" name="signup_bonus_days" min="1" max="30" value="{{ old('signup_bonus_days', $affiliate['signup_bonus_days']) }}">
                    </div>
                </div>
                <p class="mt-2 text-xs text-ink-muted">Applies to first 1-month purchase only. Coupon codes stack on top. Set 0% or disable to turn off.</p>
            </div>
            <div class="border-t border-line pt-4 space-y-3">
                <p class="text-sm font-semibold text-ink">Monthly affiliate report email</p>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="monthly_email_enabled" value="1" class="rounded" @checked(old('monthly_email_enabled', $affiliate['monthly_email_enabled']))>
                    <span>Send monthly earnings report on the 1st of each month</span>
                </label>
                <div>
                    <label class="ui-label">Minimum payable balance for monthly report (₹)</label>
                    <input class="ui-input w-32" type="number" name="monthly_email_min_payable" min="0" value="{{ old('monthly_email_min_payable', $affiliate['monthly_email_min_payable']) }}">
                    <p class="mt-1 text-xs text-ink-muted">Only affiliates with more than this available balance and earnings in the report month receive the automated email.</p>
                </div>
                <div>
                    <label class="ui-label">PayPal commission hold (months)</label>
                    <input class="ui-input w-32" type="number" name="paypal_hold_months" min="1" max="12" value="{{ old('paypal_hold_months', $affiliate['paypal_hold_months']) }}">
                    <p class="mt-1 text-xs text-ink-muted">PayPal commissions stay on hold for this period due to refund policy. UPI/offline commissions are available immediately.</p>
                </div>
            </div>
            <button type="submit" class="ui-btn-primary">Save Affiliate</button>
        </form>

        <form method="POST" action="{{ route('admin.settings.wallet') }}" id="wallet" class="dash-card space-y-4">
            @csrf
            @method('PUT')
            <h3 class="dash-card-title">Wallet</h3>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="wallet_enabled" value="1" class="rounded" @checked(old('wallet_enabled', $wallet['enabled']))>
                <span>Enable SemrushToolz Wallet (INR)</span>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Affiliate → wallet bonus %</label>
                    <input class="ui-input" type="number" name="wallet_affiliate_bonus_percent" min="0" max="100" step="0.1" value="{{ old('wallet_affiliate_bonus_percent', $wallet['affiliate_transfer_bonus_percent'] * 100) }}" required>
                </div>
                <div>
                    <label class="ui-label">Purchase cashback %</label>
                    <input class="ui-input" type="number" name="wallet_cashback_percent" min="0" max="100" step="0.1" value="{{ old('wallet_cashback_percent', $wallet['purchase_cashback_percent'] * 100) }}" required>
                </div>
            </div>
            <div>
                <label class="ui-label">Min affiliate transfer (₹)</label>
                <input class="ui-input w-32" type="number" name="wallet_min_affiliate_transfer" min="1" value="{{ old('wallet_min_affiliate_transfer', $wallet['min_affiliate_transfer']) }}" required>
            </div>
            <p class="text-xs text-ink-muted">Top-up amounts are fixed: ₹{{ implode(', ₹', $wallet['topup_amounts']) }}. Cashback applies to external subscription payments only (not wallet or top-ups).</p>
            <button type="submit" class="ui-btn-primary">Save Wallet</button>
        </form>

        <form method="POST" action="{{ route('admin.settings.support') }}" class="dash-card lg:col-span-2 space-y-4">
            @csrf
            @method('PUT')
            <h3 class="dash-card-title">Support FAQs</h3>
            <p class="text-sm text-ink-secondary">These FAQs appear on the user dashboard Support page.</p>
            @php $faqs = old('faqs', $support['faqs']); @endphp
            @foreach ($faqs as $i => $faq)
                <div class="grid gap-3 rounded-xl border border-line p-4 md:grid-cols-2">
                    <input class="ui-input" name="faqs[{{ $i }}][q]" value="{{ $faq['q'] ?? '' }}" placeholder="Question">
                    <textarea class="ui-input" name="faqs[{{ $i }}][a]" rows="2" placeholder="Answer">{{ $faq['a'] ?? '' }}</textarea>
                </div>
            @endforeach
            <div class="grid gap-3 rounded-xl border border-dashed border-line p-4 md:grid-cols-2">
                <input class="ui-input" name="faqs[{{ count($faqs) }}][q]" placeholder="New question">
                <textarea class="ui-input" name="faqs[{{ count($faqs) }}][a]" rows="2" placeholder="New answer"></textarea>
            </div>
            <button type="submit" class="ui-btn-primary">Save FAQs</button>
        </form>

        <div class="dash-card lg:col-span-2">
            <h3 class="dash-card-title">Email (Mail Panel)</h3>
            <p class="mt-2 text-sm text-ink-secondary">OTP, signup, payments, and plan expiry emails are sent via the Mail Panel API.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('admin.email-settings.edit') }}" class="ui-btn-primary">Open Email Setup →</a>
                <a href="{{ route('admin.email-presets.index') }}" class="ui-btn-outline">Manage Presets</a>
            </div>
        </div>

        <div class="dash-card lg:col-span-2">
            <h3 class="dash-card-title">Payment (UPI + PayPal)</h3>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('admin.payment-integration.edit') }}" class="ui-btn-primary">Open Payment Integration →</a>
                <a href="{{ route('admin.payments') }}" class="ui-btn-outline">View Payments Status</a>
            </div>
        </div>
    </div>
@endsection
