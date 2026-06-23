@extends('layouts.dashboard')

@section('title', 'Shop')

@section('content')
    <h1 class="dash-page-title">Shop Tools &amp; Bundles</h1>

    @if ($walletEnabled ?? false)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-success/30 bg-success/5 px-5 py-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-success">SemrushToolz Wallet</p>
                <p class="mt-1 text-2xl font-extrabold text-ink">{{ $walletBalanceLabel }}</p>
                <p class="mt-1 text-sm text-ink-muted">Pay instantly for INR subscriptions from your wallet balance.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.wallet') }}" class="ui-btn-outline">View Wallet</a>
                <a href="{{ route('dashboard.wallet.topup') }}" class="ui-btn-primary">Add Money</a>
            </div>
        </div>
    @endif

    @if (count($bundlePlans))
        @php $combo = collect($bundlePlans)->firstWhere('id', 'combo') ?? $bundlePlans[0] ?? null; @endphp
        @if ($combo)
            <div class="dash-combo-hero">
                <span class="absolute right-6 top-6 rounded-full bg-accent px-3 py-1 text-xs font-bold">⭐ BEST VALUE</span>
                <h2 class="relative text-2xl font-extrabold">{{ $combo['name'] }}</h2>
                <p class="relative mt-1 text-sm text-white/70">{{ $combo['tagline'] }}</p>
                    <div class="relative mt-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-3xl font-extrabold text-white combo-hero-price" data-monthly-inr="{{ $combo['price_inr'] }}" data-monthly-usd="{{ $combo['price_usd'] }}">₹{{ number_format($combo['price_inr']) }}<small class="text-sm font-normal text-white/60">/month</small></div>
                    <a href="{{ \App\Support\CheckoutLink::forPlan($combo['id']) }}" data-checkout-plan="{{ $combo['id'] }}" class="ui-btn-primary px-6 py-3">Subscribe Now</a>
                </div>
            </div>
        @endif
    @endif

    <x-pricing-controls />

    @forelse ($toolGroups as $category => $tools)
        <h2 class="mb-4 mt-8 text-lg font-bold text-ink">{{ \App\Models\Tool::categories()[$category] ?? ucfirst($category) }}</h2>
        <div class="shop-tools-grid mb-6">
            @foreach ($tools as $index => $tool)
                <x-shop-tool-card :tool="$tool" :index="$index" :checkout="true" />
            @endforeach
        </div>
    @empty
        <div class="dash-card text-center text-sm text-ink-muted">No tools available yet. Add tools in admin with price and “Show in shop” enabled.</div>
    @endforelse

    @if (count($bundlePlans) > 1)
        <h2 class="mb-4 text-lg font-bold text-ink">Bundle Plans</h2>
        <div class="plans-grid">
            @foreach ($bundlePlans as $index => $plan)
                @if (($plan['id'] ?? '') !== 'combo')
                    <x-plan-card :plan="$plan" :index="$index" variant="main" :checkout="true" />
                @endif
            @endforeach
        </div>
    @endif
@endsection
