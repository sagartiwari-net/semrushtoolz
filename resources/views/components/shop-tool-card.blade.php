@props([
    'tool',
    'index' => 0,
    'checkout' => false,
])

@php
    $modalId = 'tool-modal-'.($tool['id'] ?? $index);
    $features = collect($tool['features'] ?? [])->map(fn ($f) => is_array($f) ? ($f['text'] ?? '') : $f)->filter()->values();
@endphp

<div
    class="shop-tool-card animate-fade-up"
    data-price-inr="{{ $tool['price_inr'] }}"
    data-price-usd="{{ $tool['price_usd'] }}"
    data-tool-id="{{ $tool['id'] ?? '' }}"
    style="animation-delay: {{ ($index % 6) * 60 }}ms"
>
    @if (!empty($tool['badge']))
        <span class="shop-tool-badge">{{ $tool['badge'] }}</span>
    @endif

    <div class="shop-tool-logo">
        @if (!empty($tool['logo']))
            <img src="{{ $tool['logo'] }}" alt="{{ $tool['name'] }}" loading="lazy">
        @endif
    </div>

    <h3 class="shop-tool-name">{{ $tool['name'] }}</h3>

    <div class="shop-tool-price">
        <span class="plan-price-amount text-2xl font-extrabold text-ink">₹{{ number_format($tool['price_inr']) }}</span>
        <span class="text-xs text-ink-muted">/month</span>
    </div>

    <div class="shop-tool-actions">
        <button type="button" class="ui-btn-ghost w-full text-sm" onclick="document.getElementById('{{ $modalId }}').showModal()">
            View Details
        </button>
        <a href="{{ \App\Support\CheckoutLink::forTool($tool['id']) }}" data-checkout-tool="{{ $tool['id'] }}" class="ui-btn-primary w-full text-center text-sm">
            Subscribe
        </a>
    </div>
</div>

<dialog id="{{ $modalId }}" class="shop-tool-dialog" onclick="if (event.target === this) this.close()">
    <div class="shop-tool-dialog-inner">
        <button type="button" class="shop-tool-dialog-close" onclick="this.closest('dialog').close()" aria-label="Close">&times;</button>
        <div class="flex items-center gap-3">
            @if (!empty($tool['logo']))
                <img src="{{ $tool['logo'] }}" alt="" class="shop-tool-dialog-logo">
            @endif
            <div>
                <h3 class="text-lg font-bold text-ink">{{ $tool['name'] }}</h3>
                @if (!empty($tool['tagline']))
                    <p class="text-sm text-ink-muted">{{ $tool['tagline'] }}</p>
                @endif
            </div>
        </div>
        <div class="mt-3 text-2xl font-extrabold text-ink">₹{{ number_format($tool['price_inr']) }}<span class="text-sm font-normal text-ink-muted">/month</span></div>
        @if ($features->isNotEmpty())
            <ul class="mt-5 space-y-2.5">
                @foreach ($features as $text)
                    <li class="flex items-start gap-2 text-sm text-ink-secondary">
                        <svg class="mt-0.5 shrink-0 text-success" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        {{ $text }}
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="mt-6 flex gap-2">
            <a href="{{ \App\Support\CheckoutLink::forTool($tool['id']) }}" data-checkout-tool="{{ $tool['id'] }}" class="ui-btn-primary flex-1 text-center">Subscribe Now</a>
            <button type="button" class="ui-btn-outline" onclick="this.closest('dialog').close()">Close</button>
        </div>
    </div>
</dialog>
