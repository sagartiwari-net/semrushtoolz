@extends('layouts.admin')

@section('title', $plan->exists ? 'Edit Plan' : 'Add Plan')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.plans.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Plans</a>
        <h1 class="dash-page-title mt-2">{{ $plan->exists ? 'Edit Plan' : 'Add Plan' }}</h1>
        @if ($plan->exists)
            <p class="mt-1 text-sm text-ink-muted">Plan ID: <strong class="font-mono text-ink">#{{ $plan->id }}</strong> — sent automatically to proxy/extension servers for access verification.</p>
        @endif
    </div>

    <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="dash-card max-w-3xl space-y-5">
        @csrf
        @if ($plan->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="ui-label">Plan name</label><input class="ui-input" name="name" value="{{ old('name', $plan->name) }}" required></div>
            <div><label class="ui-label">Slug</label><input class="ui-input font-mono" name="slug" value="{{ old('slug', $plan->slug) }}" required></div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="ui-label">Price INR / month</label><input class="ui-input" type="number" name="price_inr" value="{{ old('price_inr', $plan->price_inr) }}" min="0" required></div>
            <div><label class="ui-label">Price USD / month</label><input class="ui-input" type="number" name="price_usd" value="{{ old('price_usd', $plan->price_usd) }}" min="0" required></div>
        </div>

        <div>
            <label class="ui-label">Homepage section</label>
            <select class="ui-input" name="display_group">
                <option value="main" @selected(old('display_group', $plan->display_group) === 'main')>Main Plans (Semrush section)</option>
                <option value="ahrefs" @selected(old('display_group', $plan->display_group) === 'ahrefs')>Ahrefs Plans section</option>
            </select>
            <p class="mt-1 text-xs text-ink-muted">Which block on homepage/shop this plan appears in.</p>
        </div>

        <div><label class="ui-label">Tagline</label><input class="ui-input" name="tagline" value="{{ old('tagline', $plan->tagline) }}"></div>
        <div><label class="ui-label">Badge</label><input class="ui-input" name="badge" value="{{ old('badge', $plan->badge) }}" placeholder="Best Value"></div>
        <div><label class="ui-label">Features (one per line)</label><textarea class="ui-input min-h-[100px]" name="features_text">{{ old('features_text', implode("\n", $plan->features ?? [])) }}</textarea></div>
        <input type="hidden" name="product_type" value="{{ old('product_type', $plan->product_type ?? 'custom') }}">

        <div>
            <label class="ui-label mb-2 block">Bundle includes these tools</label>
            <p class="mb-2 text-xs text-ink-muted">Plans are only for selling multiple tools together (combos). Individual tools appear in shop automatically from Tools.</p>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($tools as $tool)
                    <label class="flex items-center gap-2 rounded-lg border border-line p-3 text-sm">
                        <input type="checkbox" name="tool_ids[]" value="{{ $tool->id }}" @checked(in_array($tool->id, old('tool_ids', $plan->tools->pluck('id')->all())))>
                        <span class="flex-1">{{ $tool->name }}</span>
                        <span class="text-xs text-ink-muted">{{ $tool->accessTypeLabel() }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="ui-label">Sort order</label><input class="ui-input" type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}"></div>
            <div class="flex flex-col gap-2 pt-6 text-sm">
                <label class="flex gap-2"><input type="checkbox" name="is_bundle" value="1" @checked(old('is_bundle', $plan->is_bundle ?? true))> Bundle plan (show in shop)</label>
                <label class="flex gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $plan->is_featured))> Featured</label>
                <label class="flex gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))> Active</label>
                <label class="flex gap-2"><input type="checkbox" name="show_on_homepage" value="1" @checked(old('show_on_homepage', $plan->show_on_homepage ?? true))> Show on homepage</label>
            </div>
        </div>

        <button type="submit" class="ui-btn-primary">Save Plan</button>
    </form>
@endsection
