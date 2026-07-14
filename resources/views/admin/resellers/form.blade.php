@extends('layouts.admin')

@section('title', $isEdit ? 'Edit reseller' : 'Create reseller')

@section('content')
    <x-admin.reseller-nav />

    <div class="mb-5">
        <h1 class="dash-page-title !mb-1">{{ $isEdit ? 'Edit reseller' : 'Create reseller' }}</h1>
        <p class="text-sm text-ink-secondary">Reseller accounts are isolated from the customer dashboard and admin.</p>
    </div>

    <form method="POST"
          action="{{ $isEdit ? route('admin.resellers.update', $reseller) : route('admin.resellers.store') }}"
          class="ui-card max-w-xl space-y-4 p-5">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div>
            <label class="mb-1 block text-sm font-medium">Name</label>
            <input type="text" name="name" value="{{ old('name', $reseller->name) }}" required class="ui-input w-full">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email', $reseller->email) }}" required class="ui-input w-full">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
            <input type="password" name="password" @if(! $isEdit) required @endif class="ui-input w-full" autocomplete="new-password">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Confirm password</label>
            <input type="password" name="password_confirmation" @if(! $isEdit) required @endif class="ui-input w-full" autocomplete="new-password">
        </div>

        @if ($isEdit)
            <div>
                <label class="mb-1 block text-sm font-medium">Account status</label>
                <select name="status" class="ui-input w-full">
                    <option value="active" @selected(old('status', $reseller->status) === 'active')>Active</option>
                    <option value="blocked" @selected(old('status', $reseller->status) === 'blocked')>Blocked</option>
                </select>
            </div>
        @else
            <div>
                <label class="mb-1 block text-sm font-medium">Opening balance (INR, optional)</label>
                <input type="number" name="opening_balance" min="0" step="1" value="{{ old('opening_balance', 0) }}" class="ui-input w-full">
            </div>
        @endif

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $profile->is_active ?? true))>
            Reseller panel active
        </label>

        <div>
            <label class="mb-1 block text-sm font-medium">Monthly cancel limit</label>
            <input type="number" name="monthly_cancel_limit" min="0" max="1000" step="1"
                   value="{{ old('monthly_cancel_limit', $profile->monthly_cancel_limit) }}"
                   class="ui-input w-full" placeholder="Blank or 0 = cannot cancel">
            <p class="mt-1 text-xs text-ink-muted">How many provisions this reseller may cancel per calendar month (only within 1 hour of grant). Leave blank/0 to disable cancel.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Notes</label>
            <textarea name="notes" rows="3" class="ui-input w-full">{{ old('notes', $profile->notes) }}</textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="ui-btn-primary">{{ $isEdit ? 'Save' : 'Create' }}</button>
            <a href="{{ route('admin.resellers.index') }}" class="ui-btn-ghost">Cancel</a>
        </div>
    </form>

    @if ($isEdit)
        <div class="ui-card mt-6 max-w-xl border border-danger/30 p-5">
            <h2 class="text-base font-bold text-danger">Delete reseller</h2>
            <p class="mt-1 text-sm text-ink-secondary">Removes this reseller account, balance, ledger, pricing, and provision history. Customers they created keep their accounts.</p>
            <form method="POST" action="{{ route('admin.resellers.destroy', $reseller) }}" class="mt-4"
                  onsubmit="return confirm('Permanently delete reseller {{ $reseller->email }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-btn-outline border-danger text-danger text-xs">Delete reseller permanently</button>
            </form>
        </div>
    @endif
@endsection
