@props([
    'plan',
    'index' => 0,
    'variant' => 'main',
    'checkout' => false,
])

@php
    $featured = $plan['featured'] ?? false;
    $delay = ($index % 4) * 100;
@endphp

<div
    data-price-inr="{{ $plan['price_inr'] }}"
    data-price-usd="{{ $plan['price_usd'] }}"
    data-plan-id="{{ $plan['id'] ?? '' }}"
    @class([
        'plan-card animate-fade-up relative flex flex-col',
        'plan-card-featured ring-2 ring-accent ring-offset-2' => $featured,
        'plan-card-default' => !$featured,
    ])
    style="animation-delay: {{ $delay }}ms"
>
    @if ($featured)
        <div class="plan-shimmer-bar absolute inset-x-0 top-0 rounded-t-2xl"></div>
    @endif

    @if (!empty($plan['badge']))
        <span @class([
            'ui-badge absolute -top-3 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap shadow-sm',
            'bg-accent text-white' => $featured,
            'bg-blue/10 text-blue' => ($plan['badge'] ?? '') === 'Pro',
            'bg-purple/10 text-purple' => ($plan['badge'] ?? '') === 'Popular',
        ])>
            @if ($featured && ($plan['badge'] ?? '') === 'Best Value')
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            @endif
            {{ $plan['badge'] }}
        </span>
    @endif

    {{-- Logo(s) + title --}}
    <div class="plan-card-header flex items-center gap-4">
        <div class="plan-logo-wrap flex shrink-0 items-center gap-2">
            @if (!empty($plan['logos']))
                @foreach ($plan['logos'] as $logo)
                    <div class="plan-logo-ring flex h-[4.5rem] w-[4.5rem] shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface">
                        <img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] }}" class="plan-logo-img" loading="lazy">
                    </div>
                @endforeach
            @elseif ($variant === 'ahrefs')
                <div class="plan-logo-ring flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface">
                    <img src="https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094" alt="Ahrefs" class="plan-logo-img plan-logo-img-wordmark" loading="lazy">
                </div>
            @else
                <div class="plan-logo-ring flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface">
                    <img src="{{ $plan['logo'] }}" alt="{{ $plan['logo_alt'] ?? $plan['name'] }}" class="plan-logo-img {{ str_contains($plan['logo'] ?? '', 'ahrefs.svg') ? 'plan-logo-img-wordmark' : '' }}" loading="lazy">
                </div>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h3 @class([
                'plan-card-heading font-bold leading-tight text-ink',
                'text-lg sm:text-xl' => $featured,
                'text-base sm:text-lg' => ! $featured,
            ])>{{ $plan['name'] }}</h3>
            <p class="mt-1 text-xs text-ink-muted sm:text-sm">{{ $plan['tagline'] }}</p>
        </div>
    </div>

    {{-- Price --}}
    <div class="mt-5">
        <div class="flex flex-col gap-1">
            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <span class="plan-price-amount text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">₹{{ number_format($plan['price_inr']) }}</span>
                <span class="plan-price-period text-sm text-ink-muted">/month</span>
            </div>
            <span class="plan-price-save hidden w-fit rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-semibold text-success"></span>
        </div>
        <div class="plan-price-sub mt-1 hidden text-sm font-medium text-ink-secondary"></div>
    </div>

    @if ($variant === 'ahrefs' && !empty($plan['credits']))
        <div class="mt-4 flex gap-2">
            <span class="ui-badge bg-blue/10 text-blue">{{ $plan['credits'] }} credits</span>
            <span class="ui-badge bg-accent/10 text-accent">{{ $plan['export'] }} export</span>
        </div>
    @endif

    {{-- Features --}}
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
        href="{{ ($plan['checkout_type'] ?? 'plan') === 'tool' ? \App\Support\CheckoutLink::forTool($plan['id']) : \App\Support\CheckoutLink::forPlan($plan['id']) }}"
        @if (($plan['checkout_type'] ?? 'plan') === 'tool')
            data-checkout-tool="{{ $plan['id'] }}"
        @else
            data-checkout-plan="{{ $plan['id'] }}"
        @endif
        @class([
            'mt-6 w-full text-center',
            'ui-btn-primary' => $featured,
            'ui-btn-outline' => !$featured,
        ])
    >
        Subscribe Now
    </a>
</div>
