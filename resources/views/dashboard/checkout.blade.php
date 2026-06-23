@extends('layouts.dashboard')

@section('title', 'Checkout')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.shop') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Back to Shop</a>
        <h1 class="dash-page-title mt-2">Checkout</h1>
    </div>

    @if ($activeSubscriptions->isNotEmpty())
        <div class="mb-5 rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink-secondary">
            You already have active access:
            <strong>{{ $activeSubscriptions->map(fn ($s) => $s->plan?->name ?? $s->tool?->name)->filter()->join(', ') }}</strong>.
            This purchase will be <strong>added separately</strong> with its own validity — your existing plans stay active.
        </div>
    @endif

    @if ($isTrial ?? false)
        <div class="mb-5 rounded-xl border border-accent/30 bg-accent/5 px-4 py-3 text-sm text-ink-secondary">
            <strong>Trial plan:</strong> fixed price only — no coupons, wallet, or discounts apply. Access ends automatically when the trial period ends.
        </div>
    @endif

    @if (!empty($referralBonusHint))
        <div class="mb-5 rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-ink-secondary">
            Your <strong>{{ $referralBonusHint['percent'] }}% referral bonus</strong> applies to <strong>1-month</strong> plans only
            ({{ $referralBonusHint['days_left'] }} day{{ $referralBonusHint['days_left'] === 1 ? '' : 's' }} left). Select 1 month to claim it.
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <form method="POST" action="{{ route('dashboard.checkout.store') }}" class="dash-card space-y-6">
                @csrf
                @if ($tool)
                    <input type="hidden" name="tool" value="{{ $tool->slug }}">
                @else
                    <input type="hidden" name="plan" value="{{ $plan->slug }}">
                @endif
                @if ($isTrial ?? false)
                    <input type="hidden" name="duration_days" value="{{ $durationDays }}">
                @else
                    <input type="hidden" name="duration_months" value="{{ $durationMonths }}">
                @endif
                <input type="hidden" name="currency" value="{{ $currency }}">
                @if ($appliedCoupon)
                    <input type="hidden" name="coupon_code" value="{{ $appliedCoupon->code }}">
                @endif

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-ink-muted">Payment Method</h2>
                    @if ($walletEnabled && $currency === 'inr')
                        <p class="mt-1 text-xs text-ink-muted">
                            Wallet balance: <strong>₹{{ number_format($walletBalance, 0) }}</strong>
                            @if ($walletBalance < $totals['total'])
                                · <a href="{{ route('dashboard.wallet.topup') }}" class="text-accent hover:underline">Add money</a> to pay with wallet
                            @endif
                        </p>
                    @else
                        <p class="mt-1 text-xs text-ink-muted">PayPal accepts <strong>USD only</strong> with monthly auto-renewal.</p>
                    @endif
                    <div class="mt-3 space-y-2">
                        @foreach ($paymentMethods as $method)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition hover:border-accent/40 has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $method['id'] }}"
                                    class="mt-1"
                                    @checked(old('payment_method', $paymentMethods[0]['id'] ?? '') === $method['id'])
                                    required
                                >
                                <div>
                                    <div class="font-semibold text-ink">{{ $method['name'] }}</div>
                                    <div class="text-sm text-ink-muted">{{ $method['desc'] }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-start gap-3 text-sm text-ink-secondary">
                    <input type="checkbox" name="terms" value="1" class="mt-0.5" @checked(old('terms')) required>
                    <span>I agree to the <a href="{{ route('legal.terms') }}" class="text-accent hover:underline">Terms of Service</a> and understand that tool access is provided via shared group-buy accounts.</span>
                </label>

                <button type="submit" class="ui-btn-primary w-full py-3 text-base">Place Order</button>
            </form>
        </div>

        <div class="lg:col-span-2">
            <div class="dash-card sticky top-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-muted">Order Summary</h2>

                <div class="mt-4 border-b border-line pb-4">
                    <h3 class="text-lg font-bold text-ink">{{ $itemName }}</h3>
                    <p class="text-sm text-ink-muted">{{ $tool?->description ?? $plan?->tagline }}</p>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">Duration</dt>
                        <dd class="font-medium text-ink">{{ $durationLabel }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">Currency</dt>
                        <dd class="font-medium text-ink">{{ strtoupper($currency) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">Subtotal</dt>
                        <dd class="text-ink">
                            {{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['subtotal'], $currency === 'usd' && $totals['subtotal'] < 100 ? 2 : 0) }}
                        </dd>
                    </div>
                    @if (($totals['duration_discount'] ?? 0) > 0)
                        <div class="flex justify-between text-success">
                            <dt>Duration discount ({{ $totals['discount_percent'] }}%)</dt>
                            <dd>-{{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['duration_discount'], $currency === 'usd' && $totals['duration_discount'] < 100 ? 2 : 0) }}</dd>
                        </div>
                    @endif
                    @if (($totals['referral_bonus_discount'] ?? 0) > 0)
                        <div class="flex justify-between text-success">
                            <dt>Referral bonus ({{ $totals['referral_bonus_percent'] }}% off 1st month)</dt>
                            <dd>-{{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['referral_bonus_discount'], $currency === 'usd' && $totals['referral_bonus_discount'] < 100 ? 2 : 0) }}</dd>
                        </div>
                    @endif
                    @if (($totals['coupon_discount'] ?? 0) > 0 && $appliedCoupon)
                        <div class="flex justify-between text-success">
                            <dt>Coupon ({{ $appliedCoupon->code }})</dt>
                            <dd>-{{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['coupon_discount'], $currency === 'usd' && $totals['coupon_discount'] < 100 ? 2 : 0) }}</dd>
                        </div>
                    @endif
                    @if (($totals['gst_amount'] ?? 0) > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Taxable amount</dt>
                            <dd class="text-ink">{{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['taxable_amount'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">{{ $gstLabel ?? 'GST' }} ({{ rtrim(rtrim(number_format($totals['gst_rate'], 2), '0'), '.') }}%)</dt>
                            <dd class="text-ink">{{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['gst_amount'], 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-line pt-3 text-base">
                        <dt class="font-bold text-ink">Total</dt>
                        <dd class="font-extrabold text-accent">
                            {{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['total'], $currency === 'usd' && $totals['total'] < 100 ? 2 : 0) }}
                        </dd>
                    </div>
                    @if (! ($isTrial ?? false) && $durationMonths > 1)
                        <div class="text-xs text-ink-muted">
                            {{ $currency === 'inr' ? '₹' : '$' }}{{ number_format($totals['per_month'], $currency === 'usd' && $totals['per_month'] < 100 ? 2 : 0) }}/month effective
                        </div>
                    @endif
                </dl>

                @unless ($isTrial ?? false)
                <div class="mt-4 border-t border-line pt-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-ink-muted">Coupon code</p>
                    <form method="GET" action="{{ route('dashboard.checkout') }}" class="mt-2 flex gap-2">
                        @if ($tool)
                            <input type="hidden" name="tool" value="{{ $tool->slug }}">
                        @else
                            <input type="hidden" name="plan" value="{{ $plan->slug }}">
                        @endif
                        <input type="hidden" name="duration_months" value="{{ $durationMonths }}">
                        <input type="hidden" name="currency" value="{{ $currency }}">
                        <input
                            class="ui-input min-w-0 flex-1 font-mono text-sm uppercase"
                            name="coupon_code"
                            value="{{ $couponCode }}"
                            placeholder="DIWALI20"
                        >
                        <button type="submit" class="shrink-0 rounded-xl border border-line px-3 py-2 text-sm font-semibold text-ink hover:border-accent">Apply</button>
                    </form>
                    @if ($couponError)
                        <p class="mt-2 text-xs text-danger">{{ $couponError }}</p>
                    @elseif ($appliedCoupon)
                        <p class="mt-2 text-xs text-success">{{ $appliedCoupon->discountLabel() }} applied.</p>
                    @endif
                </div>
                @endunless

                <ul class="mt-5 space-y-2 border-t border-line pt-4">
                    @foreach (($tool?->shop_features ?? $plan?->features) ?? [] as $feature)
                        <li class="flex items-start gap-2 text-sm text-ink-secondary">
                            <svg class="mt-0.5 shrink-0 text-success" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ is_array($feature) ? ($feature['text'] ?? '') : $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
