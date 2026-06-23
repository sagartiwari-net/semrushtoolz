@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="dash-card">
            <div class="flex flex-col items-center text-center">
                @php
                    $avatarUser = [
                        'name' => $admin['name'],
                        'initials' => $admin['initials'],
                        'avatar_url' => $admin['avatar_url'],
                    ];
                @endphp
                <x-dashboard.user-avatar :user="$avatarUser" size="lg" class="mb-4" />

                <form method="POST" action="{{ route('admin.profile.avatar') }}" enctype="multipart/form-data" class="w-full">
                    @csrf
                    <label class="ui-btn-outline w-full cursor-pointer text-center text-sm">
                        Upload photo
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>

                @if ($admin['avatar_url'])
                    <form method="POST" action="{{ route('admin.profile.avatar.remove') }}" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mt-2 w-full text-xs text-ink-muted hover:text-danger">Remove photo</button>
                    </form>
                @endif

                <h3 class="mt-5 font-bold text-ink">{{ $admin['name'] }}</h3>
                <p class="text-sm text-ink-muted">{{ $admin['email'] }}</p>
                <span class="ui-badge mt-3 bg-ink/10 text-ink">{{ $admin['role'] }}</span>
            </div>

            <div class="mt-6 space-y-2 border-t border-line pt-6 text-sm text-ink-secondary">
                <div class="flex justify-between"><span>Admin since</span><span class="font-medium text-ink">{{ $admin['member_since'] }}</span></div>
            </div>
        </div>

        <div class="dash-card lg:col-span-2 space-y-8">
            @if ($errors->any())
                <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section>
                <h3 class="mb-4 text-base font-semibold text-ink">Account details</h3>
                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="ui-label" for="name">Full name</label>
                            <input class="ui-input" id="name" name="name" value="{{ old('name', $admin['name']) }}" required>
                        </div>
                        <div>
                            <label class="ui-label" for="phone">Phone (optional)</label>
                            <input class="ui-input" id="phone" name="phone" value="{{ old('phone', $admin['phone']) }}">
                        </div>
                    </div>
                    <div>
                        <label class="ui-label" for="email">Email address</label>
                        <input class="ui-input" id="email" name="email" type="email" value="{{ old('email', $admin['email']) }}" required>
                    </div>
                    <button type="submit" class="ui-btn-primary">Save profile</button>
                </form>
            </section>

            <section class="border-t border-line pt-8">
                <h3 class="mb-4 text-base font-semibold text-ink">Change password</h3>
                <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
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
