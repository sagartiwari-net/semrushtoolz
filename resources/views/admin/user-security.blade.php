@extends('layouts.admin')

@section('title', 'User Security — '.$user->name)

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.security') }}" class="text-sm text-accent hover:underline">← Back to Security</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="dash-card lg:col-span-1">
            <h3 class="dash-card-title">User Info</h3>
            <div class="space-y-3 text-sm">
                <div><span class="text-ink-muted">Name:</span> <strong>{{ $user->name }}</strong></div>
                <div><span class="text-ink-muted">Email:</span> {{ $user->email }}</div>
                <div><span class="text-ink-muted">Status:</span>
                    @if ($user->status === 'blocked')
                        <span class="dash-badge-offline">Blocked</span>
                    @else
                        <span class="dash-badge-online">{{ ucfirst($user->status) }}</span>
                    @endif
                </div>
                <div><span class="text-ink-muted">Security Alerts:</span> {{ $user->security_alert_count }}</div>
                @if ($user->blocked_at)
                    <div class="rounded-xl bg-danger/10 p-3 text-xs text-danger">
                        <strong>Blocked:</strong> {{ $user->blocked_at->format('M d, Y H:i') }}<br>
                        <strong>Reason:</strong> {{ $user->block_reason }}
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-col gap-2">
                @if ($sessions->isNotEmpty())
                    <form method="POST" action="{{ route('admin.security.kill-sessions', $user) }}" onsubmit="return confirm('End all active sessions for this user? They can sign in again immediately.')">
                        @csrf
                        <button type="submit" class="ui-btn-outline w-full border-warning text-warning hover:bg-warning/10">
                            Kill All Sessions ({{ $sessions->count() }})
                        </button>
                    </form>
                @endif
                @if ($user->status === 'blocked')
                    <form method="POST" action="{{ route('admin.security.unblock', $user) }}">
                        @csrf
                        <button type="submit" class="ui-btn-primary w-full">Unblock User</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.security.block', $user) }}" onsubmit="return confirm('Block this user immediately? All sessions will be killed.')">
                        @csrf
                        <textarea name="reason" class="ui-input mb-2 text-sm" rows="2" placeholder="Block reason (required)" required></textarea>
                        <button type="submit" class="ui-btn-primary w-full !bg-danger hover:!bg-danger">Block User Immediately</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="dash-card lg:col-span-2">
            <h3 class="dash-card-title">IP Activity (Last 7 Days)</h3>
            <div class="dash-stats-grid-3up mb-4">
                <x-dashboard.stat-card label="Total Requests" :value="$summary['total_requests']" icon="blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/></svg>
                </x-dashboard.stat-card>
                <x-dashboard.stat-card label="Unique IPs" :value="$summary['unique_ips']" :value-class="$summary['unique_ips'] >= config('security.max_ips_per_day') ? 'text-danger' : ''" icon="orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                </x-dashboard.stat-card>
                <x-dashboard.stat-card label="Alerts" :value="$alerts->where('status', 'open')->count()" icon="green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/></svg>
                </x-dashboard.stat-card>
            </div>

            <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-ink-muted">IP Breakdown</h4>
            <div class="dash-table-wrap mb-6">
                <table class="dash-table">
                    <thead><tr><th>IP Address</th><th>Requests</th><th>Last Seen</th><th>Devices</th></tr></thead>
                    <tbody>
                        @forelse ($summary['ip_breakdown'] as $ip => $data)
                            <tr>
                                <td class="font-mono font-medium text-ink">{{ $ip }}</td>
                                <td>{{ $data['count'] }}</td>
                                <td>{{ $data['last_seen']->diffForHumans() }}</td>
                                <td>{{ implode(', ', $data['devices']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-ink-muted">No activity logged yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-ink-muted">Recent Access Log</h4>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead><tr><th>Time</th><th>IP</th><th>Device</th><th>Browser</th><th>Platform</th><th>Page</th></tr></thead>
                    <tbody>
                        @foreach ($summary['recent_logs'] as $log)
                            <tr>
                                <td class="text-xs">{{ $log->logged_at->format('M d, H:i') }}</td>
                                <td class="font-mono text-xs">{{ $log->ip_address }}</td>
                                <td>{{ $log->device_type }}</td>
                                <td>{{ $log->browser }}</td>
                                <td>{{ $log->platform }}</td>
                                <td class="text-xs">{{ $log->route ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dash-card mt-6">
        <h3 class="dash-card-title">Active Login Sessions</h3>
        <p class="mb-4 text-sm text-ink-muted">Use this when a user is stuck on “already logged in” or you need to force logout everywhere.</p>
        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Last active</th>
                        <th>IP</th>
                        <th>Device fingerprint</th>
                        <th>Browser</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session['last_activity'] }}</td>
                            <td class="font-mono text-xs">{{ $session['ip'] }}</td>
                            <td class="font-mono text-xs">{{ $session['fingerprint'] }}</td>
                            <td class="max-w-xs truncate text-xs" title="{{ $session['user_agent'] }}">{{ Str::limit($session['user_agent'], 48) }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.security.sessions.destroy', $session['id']) }}" onsubmit="return confirm('End this session?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ui-btn-ghost text-xs text-danger">Kill</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-ink-muted">No active sessions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
