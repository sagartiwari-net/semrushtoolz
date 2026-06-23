@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
    <h1 class="dash-page-title">Settings</h1>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('dashboard.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="dash-card">
                <h3 class="dash-card-title">Notifications</h3>
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-ink">Email notifications</div>
                            <div class="text-xs text-ink-muted">Order updates and system alerts</div>
                        </div>
                        <input type="checkbox" name="notify_email" value="1" class="toggle toggle-sm toggle-primary" @checked($preferences['notify_email'])>
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-ink">Expiry reminders</div>
                            <div class="text-xs text-ink-muted">Get notified before plan expires</div>
                        </div>
                        <input type="checkbox" name="notify_expiry" value="1" class="toggle toggle-sm toggle-primary" @checked($preferences['notify_expiry'])>
                    </label>
                    <label class="flex cursor-pointer items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-ink">Login alerts</div>
                            <div class="text-xs text-ink-muted">Notify on new device login</div>
                        </div>
                        <input type="checkbox" name="notify_login" value="1" class="toggle toggle-sm toggle-primary" @checked($preferences['notify_login'])>
                    </label>
                </div>
            </div>

            <div class="dash-card">
                <h3 class="dash-card-title">Preferences</h3>
                <div class="space-y-4">
                    <div>
                        <label class="ui-label">Timezone</label>
                        <select class="ui-input" name="timezone">
                            <option value="Asia/Kolkata" @selected($preferences['timezone'] === 'Asia/Kolkata')>Asia/Kolkata (IST)</option>
                            <option value="UTC" @selected($preferences['timezone'] === 'UTC')>UTC</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-label">Theme</label>
                        <select class="ui-input" name="theme">
                            <option value="light" @selected($preferences['theme'] === 'light')>Light</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="dash-card lg:col-span-2">
                <h3 class="dash-card-title">Recent Login Activity</h3>
                <p class="mb-4 text-xs text-ink-muted">We monitor IP addresses to prevent account sharing. Using your account from multiple locations may result in automatic suspension.</p>
                <div class="space-y-3">
                    @forelse ($loginLogs as $log)
                        <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3">
                            <div>
                                <div class="text-sm font-medium text-ink">{{ $log->platform ?? 'Device' }} — {{ $log->browser ?? 'Browser' }}</div>
                                <div class="text-xs text-ink-muted">{{ $log->ip_address ?? '—' }} · {{ ($log->logged_at ?? $log->created_at)?->diffForHumans() }}</div>
                            </div>
                            <span class="dash-badge-online">{{ ucfirst($log->action ?? 'login') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">No login history recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <button type="submit" class="ui-btn-primary mt-4">Save Settings</button>
    </form>
@endsection
