@extends('layouts.reseller')

@section('title', 'Provision access')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Provision access</h1>
        <p class="text-sm text-ink-secondary">Charge is flat monthly price × months (no duration discount).</p>
    </div>

    @if ($catalog->isEmpty())
        <div class="rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-warning">
            No tools are priced for your account yet. Ask admin to set reseller pricing.
        </div>
    @else
        <form method="POST" action="{{ route('reseller.provision.store') }}" class="ui-card max-w-xl space-y-4 p-5" id="provision-form">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-ink">Customer email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="ui-input w-full" placeholder="customer@gmail.com">
                <p class="mt-1 text-xs text-ink-muted">No email verification — access is granted immediately. Temporary / disposable emails are blocked.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink">Tool / Plan</label>
                <select name="tool_id" id="tool_id" required class="ui-input w-full">
                    <option value="">Select…</option>
                    @foreach ($catalog as $item)
                        <option value="{{ $item['tool']->id }}"
                            data-price="{{ $item['price_inr'] }}"
                            @selected(old('tool_id') == $item['tool']->id)>
                            {{ $item['tool']->name }} — ₹{{ number_format($item['price_inr']) }}/mo
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink">Duration</label>
                <select name="duration_months" id="duration_months" required class="ui-input w-full">
                    @foreach ($durations as $m)
                        <option value="{{ $m }}" @selected(old('duration_months', 1) == $m)>{{ $m }} month{{ $m > 1 ? 's' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rounded-xl bg-surface-2 px-4 py-3 text-sm">
                <span class="text-ink-secondary">Charge preview:</span>
                <strong id="charge-preview" class="ml-1 text-ink">—</strong>
            </div>
            <label class="flex items-start gap-2 text-sm text-ink">
                <input type="checkbox" name="confirm_charge" value="1" class="mt-1" required>
                <span>I confirm the charge shown above will be deducted from my balance.</span>
            </label>
            <button type="submit" class="ui-btn-primary">Provision access</button>
        </form>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        const tool = document.getElementById('tool_id');
        const months = document.getElementById('duration_months');
        const preview = document.getElementById('charge-preview');
        if (!tool || !months || !preview) return;

        function update() {
            const opt = tool.options[tool.selectedIndex];
            const price = parseFloat(opt?.dataset?.price || '0');
            const m = parseInt(months.value || '0', 10);
            if (!price || !m) {
                preview.textContent = '—';
                return;
            }
            const total = price * m;
            preview.textContent = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        tool.addEventListener('change', update);
        months.addEventListener('change', update);
        update();
    })();
</script>
@endpush
