@php
    $durations = \App\Services\PricingService::durations();
@endphp

<script>
    window.PricingConfig = @json(\App\Services\PricingService::jsConfig());
</script>

<div data-pricing-root class="pricing-controls ui-card mx-auto mb-10 max-w-6xl p-5 sm:p-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        {{-- Duration --}}
        <div class="flex-1">
            <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-muted">Billing Period</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($durations as $months => $duration)
                    <button
                        type="button"
                        data-duration-btn="{{ $months }}"
                        @class([
                            'relative rounded-xl border border-line px-3.5 py-2 text-sm font-medium transition-all hover:border-ink/20',
                            'bg-ink text-white' => $months === 1,
                            'text-ink-secondary' => $months !== 1,
                        ])
                    >
                        {{ $duration['label'] }}
                        @if ($duration['discount'] > 0)
                            <span class="ml-1 rounded-md bg-success/15 px-1.5 py-0.5 text-[10px] font-bold text-success">
                                -{{ (int) ($duration['discount'] * 100) }}%
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Currency --}}
        <div data-currency-switcher>
            <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-muted">Currency</p>
            <div class="inline-flex rounded-xl border border-line bg-surface p-1">
                <button
                    type="button"
                    data-currency-btn="inr"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition-all bg-accent text-white"
                >
                    ₹ INR
                </button>
                <button
                    type="button"
                    data-currency-btn="usd"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition-all text-ink-secondary"
                >
                    $ USD
                </button>
            </div>
            <p data-currency-locked class="hidden text-xs text-ink-muted">Prices shown in USD for international users</p>
        </div>
    </div>

    {{-- Payment methods --}}
    <div class="mt-5 border-t border-line pt-5">
        <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-muted">Payment Methods</p>
        <div data-payment-methods class="flex flex-wrap gap-2"></div>
        <p data-payment-note class="mt-2.5 text-xs text-ink-muted"></p>
    </div>
</div>
