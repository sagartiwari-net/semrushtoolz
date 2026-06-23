@extends('layouts.dashboard')

@section('title', 'Profile')

@section('content')
    <h1 class="dash-page-title">My Profile</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="dash-card">
            <div class="flex flex-col items-center text-center">
                <x-dashboard.user-avatar :user="$user" size="lg" class="mb-4" />

                <form method="POST" action="{{ route('dashboard.profile.avatar') }}" enctype="multipart/form-data" class="w-full space-y-3">
                    @csrf
                    <label class="ui-btn-outline w-full cursor-pointer text-center text-sm">
                        Upload photo
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>

                @if ($user['avatar_url'])
                    <form method="POST" action="{{ route('dashboard.profile.avatar.remove') }}" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mt-2 w-full text-xs text-ink-muted hover:text-danger">Remove photo</button>
                    </form>
                @endif

                <p class="mt-3 text-xs text-ink-muted">JPG, PNG or WebP · max 2 MB</p>

                <h3 class="mt-5 font-bold text-ink">{{ $user['name'] }}</h3>
                <p class="text-sm text-ink-muted">{{ $user['email'] }}</p>
                <span class="ui-badge mt-3 bg-accent/10 text-accent">{{ $user['plan'] }}</span>
            </div>

            <div class="mt-6 space-y-2 border-t border-line pt-6 text-sm text-ink-secondary">
                <div class="flex justify-between"><span>Member since</span><span class="font-medium text-ink">{{ $user['member_since'] }}</span></div>
                <div class="flex justify-between"><span>Status</span><span class="font-medium text-success">{{ $user['plan_status'] }}</span></div>
                <div class="flex justify-between"><span>Expires</span><span class="font-medium text-ink">{{ $user['expires_at'] }}</span></div>
                <div class="flex justify-between"><span>Email verified</span>
                    <span @class(['font-medium', 'text-success' => $user['email_verified'], 'text-warning' => ! $user['email_verified']])>
                        {{ $user['email_verified'] ? 'Yes' : 'Pending' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="dash-card lg:col-span-2 space-y-8">
            <section>
                <h3 class="mb-4 text-base font-semibold text-ink">Account details</h3>
                <form method="POST" action="{{ route('dashboard.profile.update') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="ui-label" for="name">Full name</label>
                            <input class="ui-input" id="name" name="name" value="{{ old('name', $user['name']) }}" required>
                        </div>
                        <div>
                            <label class="ui-label" for="phone">Phone (optional)</label>
                            <input class="ui-input" id="phone" name="phone" value="{{ old('phone', $user['phone']) }}" placeholder="+91 98765 43210">
                        </div>
                    </div>
                    <div>
                        <label class="ui-label" for="email">Email address</label>
                        <input class="ui-input" id="email" name="email" type="email" value="{{ old('email', $user['email']) }}" required>
                        <p class="mt-1 text-xs text-ink-muted">A new verification link will be sent if you change your email.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="ui-label">Your referral code</label>
                            <input class="ui-input font-mono" readonly value="{{ $user['referral_code'] }}">
                            <p class="mt-1 text-xs text-ink-muted">Share from <a href="{{ route('dashboard.affiliates') }}" class="text-accent hover:underline">Affiliates</a></p>
                        </div>
                        <div>
                            <label class="ui-label">Referred by</label>
                            @if ($user['referred_by_name'])
                                <input class="ui-input" readonly value="{{ $user['referred_by_name'] }} ({{ $user['referred_by_code'] }})">
                                <p class="mt-1 text-xs text-ink-muted">Locked at signup — cannot be changed</p>
                            @else
                                <input class="ui-input text-ink-muted" readonly value="—">
                                <p class="mt-1 text-xs text-ink-muted">No referral was applied when you signed up</p>
                            @endif
                        </div>
                    </div>
                    <button type="submit" class="ui-btn-primary">Save profile</button>
                </form>
            </section>

            <section class="border-t border-line pt-8">
                <h3 class="mb-4 text-base font-semibold text-ink">Change password</h3>
                <form method="POST" action="{{ route('dashboard.profile.password') }}" class="space-y-4">
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
    </div>
@endsection
