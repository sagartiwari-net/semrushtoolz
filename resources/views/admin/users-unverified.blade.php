@extends('layouts.admin')

@section('title', 'Unverified Users')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.users') }}" class="text-sm text-accent hover:underline">← Verified users</a>
            <h1 class="dash-page-title mt-1">Unverified accounts</h1>
            <p class="text-sm text-ink-muted">Bot/fake signups that never verified email. Not shown in the main users list.</p>
        </div>
        @if ($unverifiedStats['eligible_for_purge'] > 0)
            <form method="POST" action="{{ route('admin.users.unverified.purge-eligible') }}" onsubmit="return confirm('Delete all {{ $unverifiedStats['eligible_for_purge'] }} eligible accounts?')">
                @csrf
                <button type="submit" class="ui-btn-outline border-danger text-danger text-sm">Delete all eligible ({{ $unverifiedStats['eligible_for_purge'] }})</button>
            </form>
        @endif
    </div>

    <div class="mb-4 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm">
        <strong class="text-warning">{{ $unverifiedStats['total'] }} unverified account(s)</strong>
        <span class="text-ink-secondary">
            — {{ $unverifiedStats['eligible_for_purge'] }} eligible for auto-delete
            (older than {{ $unverifiedStats['purge_after_days'] }} days, no orders, no active plan).
            Purge runs daily at 3 AM.
        </span>
    </div>

    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <div>
            <label class="ui-label text-xs">Search</label>
            <input class="ui-input max-w-xs py-1.5 text-sm" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or email…">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="ui-btn-primary text-sm">Search</button>
            <a href="{{ route('admin.users.unverified') }}" class="ui-btn-outline text-sm">Clear</a>
        </div>
    </form>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Age</th>
                    <th>Auto-delete</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="font-medium">{{ $u['name'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['joined'] }}</td>
                        <td>{{ $u['days_old'] }} day(s)</td>
                        <td>
                            @if ($u['eligible_for_purge'])
                                <span class="text-xs text-warning">Eligible</span>
                            @else
                                <span class="text-xs text-ink-muted">Not yet</span>
                            @endif
                        </td>
                        <td>
                            <span @class([
                                'dash-badge-online' => $u['status'] === 'Active',
                                'dash-badge-offline' => $u['status'] === 'Blocked',
                            ])>{{ $u['status'] }}</span>
                        </td>
                        <td class="space-x-2">
                            <a href="{{ route('admin.users.show', $u['id']) }}" class="ui-btn-ghost text-xs">Profile</a>
                            <form method="POST" action="{{ route('admin.users.unverified.delete', $u['id']) }}" class="inline" onsubmit="return confirm('Delete this unverified account?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-btn-ghost text-xs text-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-ink-muted">No unverified accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$users" :per-page="$perPage" />
    </div>
@endsection
