@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit Coupon' : 'Create Coupon')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.coupons.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Back to Coupons</a>
        <h1 class="dash-page-title mt-2">{{ $coupon->exists ? 'Edit Coupon' : 'Create Coupon' }}</h1>
        <p class="text-sm text-ink-secondary">All restriction fields are optional — leave blank to allow any.</p>
    </div>

    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="dash-card max-w-3xl space-y-6">
        @csrf
        @if ($coupon->exists)
            @method('PUT')
        @endif

        <div>
            <p class="text-sm font-semibold text-ink">Basic</p>
        </div>

        <div>
            <label class="ui-label">Coupon code *</label>
            <input class="ui-input font-mono uppercase" name="code" value="{{ old('code', $coupon->code) }}" required placeholder="DIWALI20">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ui-label">Discount type *</label>
                <select class="ui-input" name="type" id="coupon-type">
                    <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percentage (%)</option>
                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount</option>
                </select>
            </div>
            <div>
                <label class="ui-label">Value *</label>
                <input class="ui-input" type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon->value) }}" required>
                <p class="mt-1 text-xs text-ink-muted" id="value-hint">e.g. 20 for 20% off</p>
            </div>
        </div>

        <div id="currency-field">
            <label class="ui-label">Currency (fixed only, optional)</label>
            <select class="ui-input" name="currency">
                <option value="">Any currency</option>
                <option value="inr" @selected(old('currency', $coupon->currency) === 'inr')>INR (₹)</option>
                <option value="usd" @selected(old('currency', $coupon->currency) === 'usd')>USD ($)</option>
            </select>
        </div>

        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', $coupon->is_active))>
            <span>Active</span>
        </label>

        <hr class="border-line">

        <div>
            <p class="text-sm font-semibold text-ink">Usage limits (optional)</p>
            <p class="mt-1 text-xs text-ink-muted">Blank = no limit for that rule.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ui-label">Total uses (all customers)</label>
                <input class="ui-input" type="number" min="1" name="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}" placeholder="Unlimited">
            </div>
            <div>
                <label class="ui-label">Uses per customer</label>
                <input class="ui-input" type="number" min="1" name="max_uses_per_user" value="{{ old('max_uses_per_user', $coupon->max_uses_per_user) }}" placeholder="Unlimited">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ui-label">Valid from</label>
                <input class="ui-input" type="datetime-local" name="valid_from" value="{{ old('valid_from', $coupon->valid_from?->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label class="ui-label">Expires on</label>
                <input class="ui-input" type="datetime-local" name="valid_until" value="{{ old('valid_until', $coupon->valid_until?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>

        <div>
            <label class="ui-label">Minimum order value</label>
            <input class="ui-input" type="number" step="0.01" min="0" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" placeholder="Any amount">
            <p class="mt-1 text-xs text-ink-muted">After duration discount, before coupon discount.</p>
        </div>

        <hr class="border-line">

        <div>
            <p class="text-sm font-semibold text-ink">Applies to — product / plan (optional)</p>
            <p class="mt-1 text-xs text-ink-muted">Select kya kharida ja sakta hai is coupon se. Khali = koi bhi plan ya tool.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Plans</p>
                <div class="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-line p-3">
                    @forelse ($plans as $plan)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="allowed_plan_ids[]" value="{{ $plan->id }}" class="rounded"
                                @checked(in_array($plan->id, old('allowed_plan_ids', $coupon->allowed_plan_ids ?? [])))>
                            <span>{{ $plan->name }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-ink-muted">No plans</p>
                    @endforelse
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Tools / products</p>
                <div class="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-line p-3">
                    @forelse ($tools as $tool)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="allowed_tool_ids[]" value="{{ $tool->id }}" class="rounded"
                                @checked(in_array($tool->id, old('allowed_tool_ids', $coupon->allowed_tool_ids ?? [])))>
                            <span>{{ $tool->name }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-ink-muted">No tools</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Duration (optional)</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($durations as $months => $duration)
                    <label class="flex items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm">
                        <input type="checkbox" name="allowed_duration_months[]" value="{{ $months }}" class="rounded"
                            @checked(in_array((int) $months, old('allowed_duration_months', $coupon->allowed_duration_months ?? [])))>
                        <span>{{ $duration['label'] }}</span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-ink-muted">Khali = 1, 3, 6, 12 month sab par chalega.</p>
        </div>

        <hr class="border-line">

        <div>
            <p class="text-sm font-semibold text-ink">Required subscription (optional)</p>
            <p class="mt-1 text-xs text-ink-muted">Customer ke paas pehle se ye active subscription hona chahiye tab hi coupon use kar sakta hai.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Required plan</p>
                <div class="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-line p-3">
                    @foreach ($plans as $plan)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="required_plan_ids[]" value="{{ $plan->id }}" class="rounded"
                                @checked(in_array($plan->id, old('required_plan_ids', $coupon->required_plan_ids ?? [])))>
                            <span>{{ $plan->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-ink-muted">Required tool subscription</p>
                <div class="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-line p-3">
                    @foreach ($tools as $tool)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="required_tool_ids[]" value="{{ $tool->id }}" class="rounded"
                                @checked(in_array($tool->id, old('required_tool_ids', $coupon->required_tool_ids ?? [])))>
                            <span>{{ $tool->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div>
            <label class="ui-label">Internal note</label>
            <input class="ui-input" name="description" value="{{ old('description', $coupon->description) }}" placeholder="Optional admin note">
        </div>

        <button type="submit" class="ui-btn-primary">{{ $coupon->exists ? 'Save Changes' : 'Create Coupon' }}</button>
    </form>

    <script>
        const typeEl = document.getElementById('coupon-type');
        const currencyField = document.getElementById('currency-field');
        const valueHint = document.getElementById('value-hint');
        function syncType() {
            const isPercent = typeEl.value === 'percent';
            currencyField.style.display = isPercent ? 'none' : '';
            valueHint.textContent = isPercent ? 'e.g. 20 for 20% off' : 'e.g. 50 for ₹50 or $50 off';
        }
        typeEl.addEventListener('change', syncType);
        syncType();
    </script>
@endsection
