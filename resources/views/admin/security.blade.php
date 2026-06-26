@extends('layouts.admin')

@section('title', 'Security Alerts')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="dash-stats-grid-3up mb-6">
        <x-dashboard.stat-card label="Open Alerts" :value="$openCount" icon="orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Critical" :value="$criticalCount" value-class="text-danger" icon="green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Blocked Users" :value="$flaggedUsers->where('status', 'blocked')->count()" icon="blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </x-dashboard.stat-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="dash-card xl:col-span-2 !mb-0">
            <h3 class="dash-card-title">Security Alerts — Account Sharing Detection</h3>
            <div class="space-y-3">
                @forelse ($alerts as $alert)
                    <div @class([
                        'rounded-xl border p-4',
                        'border-danger/30 bg-danger/5' => $alert->severity === 'critical' && $alert->isOpen(),
                        'border-warning/30 bg-warning/5' => $alert->severity === 'high' && $alert->isOpen(),
                        'border-line bg-surface/40' => $alert->status === 'resolved',
                    ])>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-ink">{{ $alert->title }}</span>
                                    <span @class([
                                        'ui-badge bg-danger text-white' => $alert->severity === 'critical',
                                        'ui-badge bg-warning/15 text-warning' => $alert->severity === 'high',
                                    ])>{{ strtoupper($alert->severity) }}</span>
                                    @if ($alert->isOpen())
                                        <span class="dash-badge-pending">Open</span>
                                    @else
                                        <span class="dash-badge-offline">Resolved</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-ink-secondary">{{ $alert->description }}</p>
                                <p class="mt-2 text-xs text-ink-muted">{{ $alert->created_at->diffForHumans() }} · {{ $alert->user?->email }}</p>
                                @if (!empty($alert->metadata['ips']))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($alert->metadata['ips'] as $ip)
                                            <span class="rounded-md bg-ink/5 px-2 py-0.5 font-mono text-xs text-ink-secondary">{{ $ip }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.security.user', $alert->user_id) }}" class="ui-btn-outline text-xs">View Details</a>
                                @if ($alert->isOpen())
                                    @if ($alert->user && $alert->user->status !== 'blocked')
                                        <form method="POST" action="{{ route('admin.security.block', $alert->user_id) }}" onsubmit="return confirm('Block this user immediately?')">
                                            @csrf
                                            <input type="hidden" name="reason" value="Account sharing detected — {{ $alert->type }}">
                                            <button type="submit" class="ui-btn-primary text-xs !bg-danger hover:!bg-danger">Block Now</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.security.resolve', $alert) }}">
                                        @csrf
                                        <button type="submit" class="ui-btn-ghost text-xs">Resolve</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No security alerts yet. IP tracking is active on all dashboard visits.</p>
                @endforelse
            </div>
            <div class="mt-4 text-xs text-ink-muted">Showing latest {{ $alerts->count() }} alerts</div>
        </div>

        <div class="dash-card !mb-0">
            <h3 class="dash-card-title">Flagged Users</h3>
            <div class="space-y-3">
                @forelse ($flaggedUsers as $user)
                    <a href="{{ route('admin.security.user', $user) }}" class="block rounded-xl border border-line p-3 transition hover:border-accent/30">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">{{ $user->name }}</span>
                            @if ($user->status === 'blocked')
                                <span class="dash-badge-offline">Blocked</span>
                            @else
                                <span class="dash-badge-pending">{{ $user->security_alert_count }} alerts</span>
                            @endif
                        </div>
                        <p class="text-xs text-ink-muted">{{ $user->email }}</p>
                    </a>
                @empty
                    <p class="text-sm text-ink-muted">No flagged users.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dash-card mt-4">
        <h3 class="dash-card-title">How Account Sharing Detection Works</h3>
        <ul class="grid gap-2 text-sm text-ink-secondary sm:grid-cols-2">
            <li>✓ Every dashboard visit logs IP, device, browser &amp; platform</li>
            <li>✓ {{ config('security.max_ips_warning_hour') }}+ IPs + {{ config('security.min_devices_for_warning') }}+ devices in 1 hour → User warning</li>
            <li>✓ {{ config('security.max_ips_block_hour') }}+ IPs + {{ config('security.min_devices_for_block') }}+ devices in 1 hour → Auto-block</li>
            <li>✓ Same device, changing network/IP → ignored (no false block)</li>
            <li>✓ Device fingerprint blocks stolen cookies on other browsers</li>
            <li>✓ Admin can kill active sessions from User Security page</li>
        </ul>
    </div>
@endsection
