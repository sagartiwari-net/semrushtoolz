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
        <p class="section-sub mx-auto mt-2">Short trial to test tools before a full plan. Fixed price — no coupons or wallet.</p>
    </div>

    <div
        data-trial-pricing-root
        data-plan-id="{{ $plan['id'] }}"
        class="trial-plan-shell mx-auto mt-8 max-w-5xl"
    >
        <div
            class="trial-plan-card"
            data-price-inr="{{ $defaultTier['price_inr'] ?? 100 }}"
            data-price-usd="{{ $defaultTier['price_usd'] ?? 1.5 }}"
        >
            <span class="trial-plan-badge">{{ $plan['badge'] ?? 'Trial' }}</span>

            <div class="trial-plan-layout">
                <div class="trial-plan-main">
                    <div class="trial-plan-brand">
                        @if (!empty($plan['logos']))
                            <div class="trial-plan-logos">
                                @foreach ($plan['logos'] as $logo)
                                    <div class="plan-logo-ring flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface sm:h-[4.5rem] sm:w-[4.5rem]">
                                        <img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] }}" class="plan-logo-img" loading="lazy">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h3 class="text-xl font-bold leading-tight text-ink sm:text-2xl">{{ $plan['name'] }}</h3>
                            <p class="mt-1 text-sm text-ink-muted">{{ $plan['tagline'] }}</p>
                            <p class="mt-2 text-xs text-ink-muted">Includes Semrush + Ahrefs Plan 1 · <span class="font-medium text-ink-secondary">Ahrefs Bar not included</span></p>
                        </div>
                    </div>

                    <ul class="trial-plan-features">
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
                </div>

                <div class="trial-plan-side">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Choose trial length</p>
                    <div class="trial-plan-durations">
                        @foreach ($durations as $days => $tier)
                            <button
                                type="button"
                                data-trial-duration-btn="{{ $days }}"
                                data-price-inr="{{ $tier['price_inr'] }}"
                                data-price-usd="{{ $tier['price_usd'] }}"
                                @class([
                                    'trial-duration-btn',
                                    'is-active' => $days === $defaultDays,
                                ])
                            >
                                <span class="font-semibold">{{ $tier['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="trial-plan-price-box">
                        <span class="trial-price-amount text-4xl font-extrabold tracking-tight text-ink">
                            ₹{{ number_format($defaultTier['price_inr'] ?? 100) }}
                        </span>
                        <span class="text-sm text-ink-muted">one-time trial</span>
                        <p class="mt-2 text-xs leading-relaxed text-ink-muted">No discounts · No coupon · No wallet</p>
                    </div>

                    <a
                        href="{{ \App\Support\CheckoutLink::forTrialPlan($plan['id'], $defaultDays) }}"
                        data-trial-checkout
                        class="ui-btn-primary w-full py-3 text-center text-base"
                    >
                        Start Trial
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
