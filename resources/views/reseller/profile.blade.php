@extends('layouts.reseller')

@section('title', 'My Profile')

@section('content')
    <div class="dash-card max-w-2xl space-y-8">
        <section>
            <h3 class="mb-4 text-base font-semibold text-ink">Account details</h3>
            <form method="POST" action="{{ route('reseller.profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label class="ui-label" for="name">Full name</label>
                    <input class="ui-input" id="name" name="name" value="{{ old('name', $reseller['name']) }}" required>
                </div>
                <div>
                    <label class="ui-label" for="email">Email address</label>
                    <input class="ui-input" id="email" type="email" value="{{ $reseller['email'] }}" readonly>
                    <p class="mt-1 text-xs text-ink-muted">Email cannot be changed here. Contact admin if you need a new login email.</p>
                </div>
                <div>
                    <label class="ui-label" for="phone">Phone (optional)</label>
                    <input class="ui-input" id="phone" name="phone" value="{{ old('phone', $reseller['phone']) }}">
                </div>
                <button type="submit" class="ui-btn-primary">Save profile</button>
            </form>
        </section>

        <section class="border-t border-line pt-8">
            <h3 class="mb-4 text-base font-semibold text-ink">Change password</h3>
            <p class="mb-4 text-sm text-ink-muted">Use a strong password. After updating, you stay logged in on this device.</p>
            <form method="POST" action="{{ route('reseller.profile.password') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="ui-label">Current password</label>
                    <input class="ui-input" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="ui-label">New password</label>
                        <input class="ui-input" type="password" name="password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="ui-label">Confirm new password</label>
                        <input class="ui-input" type="password" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="ui-btn-outline">Update password</button>
            </form>
        </section>
    </div>
@endsection
