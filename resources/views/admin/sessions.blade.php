@extends('layouts.admin')

@section('title', 'Sessions')

@section('content')
    <div class="dash-stats-grid-3up mb-6">
        <x-dashboard.stat-card label="Live Sessions" :value="(string) $liveSessions" sub="Across all tools" icon="blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </x-dashboard.stat-card>
        @foreach ($seatCards as $card)
            <x-dashboard.stat-card :label="$card['label']" :value="$card['value']" :icon="$card['icon']">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/></svg>
            </x-dashboard.stat-card>
        @endforeach
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>User</th><th>Tool</th><th>Started</th><th>Duration</th><th>IP</th><th></th></tr></thead>
            <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td class="font-medium text-ink">{{ $session['user'] }}</td>
                        <td>{{ $session['tool'] }}</td>
                        <td>{{ $session['started'] }}</td>
                        <td>{{ $session['duration'] }}</td>
                        <td>{{ $session['ip'] }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.sessions.destroy', $session['id']) }}" onsubmit="return confirm('End this session?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-btn-ghost text-xs text-danger">Kill</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-ink-muted">No active sessions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
