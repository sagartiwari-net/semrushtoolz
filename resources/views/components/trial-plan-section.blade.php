@props(['plan'])

@php
    $durations = \App\Services\PricingService::trialDurations();
    $defaultDays = (int) array_key_first($durations);
    $defaultTier = $durations[$defaultDays] ?? null;
@endphp

<div class="mt-14 border-t border-line pt-12" id="trial-plan">
    <div class="text-center">
        <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-purple/10 px-3 py-1 text-xs font-semibold text-purple">
            Trial Access
        </div>
        <h3 class="text-2xl font-extrabold text-ink sm:text-3xl">Try Semrush + Ahrefs Combo</h3>
        <p class="section-sub mx-auto mt-2">Test the full combo before subscribing. Fixed trial price — no coupons or wallet.</p>
    </div>

    <div
        data-trial-pricing-root
        data-plan-id="{{ $plan['id'] }}"
        class="mx-auto mt-8 max-w-xl"
    >
        <div
            class="plan-card plan-card-default relative flex flex-col p-6 lg:p-8"
            data-price-inr="{{ $defaultTier['price_inr'] ?? 100 }}"
            data-price-usd="{{ $defaultTier['price_usd'] ?? 1.5 }}"
        >
            <span class="ui-badge absolute -top-3 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap bg-purple/10 text-purple shadow-sm">
                {{ $plan['badge'] ?? 'Trial' }}
            </span>

            <div class="plan-card-header flex items-center gap-4">
                <div class="plan-logo-wrap flex shrink-0 items-center gap-2">
                    @if (!empty($plan['logos']))
                        @foreach ($plan['logos'] as $logo)
                            <div class="plan-logo-ring flex h-[4.5rem] w-[4.5rem] shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface">
                                <img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] }}" class="plan-logo-img" loading="lazy">
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="plan-card-heading text-base font-bold leading-tight text-ink sm:text-lg">{{ $plan['name'] }}</h3>
                    <p class="mt-1 text-xs text-ink-muted sm:text-sm">{{ $plan['tagline'] }}</p>
                </div>
            </div>

            <div class="mt-5">
                <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-muted">Trial Length</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($durations as $days => $tier)
                        <button
                            type="button"
                            data-trial-duration-btn="{{ $days }}"
                            data-price-inr="{{ $tier['price_inr'] }}"
                            data-price-usd="{{ $tier['price_usd'] }}"
                            @class([
                                'rounded-xl border border-line px-3.5 py-2 text-sm font-medium transition-all hover:border-ink/20',
                                'bg-ink text-white' => $days === $defaultDays,
                                'text-ink-secondary' => $days !== $defaultDays,
                            ])
                        >
                            {{ $tier['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="trial-price-amount text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
                        ₹{{ number_format($defaultTier['price_inr'] ?? 100) }}
                    </span>
                    <span class="trial-price-period text-sm text-ink-muted">trial</span>
                </div>
                <p class="mt-2 text-xs text-ink-muted">No duration discounts · No coupon codes · No wallet payment</p>
            </div>

            <ul class="mt-5 flex-1 space-y-2.5">
                @foreach ($plan['features'] as $feature)
                    @php
                        $text = is_array($feature) ? $feature['text'] : $feature;
                        $marker = is_array($feature) ? ($feature['marker'] ?? '') : '';
                    @endphp
                    <li class="flex items-start gap-2 text-sm text-ink-secondary">
                        <svg class="check-pop mt-0.5 shrink-0 text-success" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>{{ $text }}@if($marker)<sup class="font-bold text-accent">{{ $marker }}</sup>@endif</span>
                    </li>
                @endforeach
            </ul>

            <a
                href="{{ \App\Support\CheckoutLink::forTrialPlan($plan['id'], $defaultDays) }}"
                data-trial-checkout
                class="ui-btn-primary mt-6 w-full text-center"
            >
                Start Trial
            </a>
        </div>
    </div>
</div>
