@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <form method="GET" class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-1 flex-wrap gap-2">
            <div>
                <label class="ui-label text-xs">Search</label>
                <input class="ui-input max-w-xs py-1.5 text-sm" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email, referral code…">
            </div>
            <div>
                <label class="ui-label text-xs">Status</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="status">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Blocked</option>
                </select>
            </div>
            <button type="submit" class="ui-btn-outline text-sm">Search</button>
        </div>
    </form>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Status</th><th>Joined</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="font-medium text-ink">{{ $u['name'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['plan'] }}</td>
                        <td>
                            <span @class([
                                'dash-badge-online' => $u['status'] === 'Active',
                                'dash-badge-offline' => $u['status'] === 'Blocked',
                                'dash-badge-pending' => ! in_array($u['status'], ['Active', 'Blocked']),
                            ])>{{ $u['status'] }}</span>
                        </td>
                        <td>{{ $u['joined'] }}</td>
                        <td class="flex gap-2">
                            <a href="{{ route('admin.security.user', $u['id']) }}" class="ui-btn-ghost text-xs">Security</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-ink-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$users" :per-page="$perPage" />
    </div>
@endsection
