@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    @if ($unverifiedStats['total'] > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm">
            <div>
                <strong class="text-warning">{{ $unverifiedStats['total'] }} unverified account(s)</strong>
                <span class="text-ink-secondary"> — likely bot/fake signups. {{ $unverifiedStats['eligible_for_purge'] }} eligible for auto-delete (older than {{ $unverifiedStats['purge_after_days'] }} days, no orders).</span>
            </div>
            <a href="{{ route('admin.users', ['verified' => 'no']) }}" class="ui-btn-outline text-xs border-warning text-warning">View unverified only</a>
        </div>
    @endif

    <form method="GET" class="mb-5 space-y-3">
        <div class="flex flex-wrap gap-2">
            <div>
                <label class="ui-label text-xs">Search</label>
                <input class="ui-input max-w-xs py-1.5 text-sm" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email, referral code…">
            </div>
            <div>
                <label class="ui-label text-xs">Account status</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="status">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Blocked</option>
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Email verified</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="verified">
                    <option value="all" @selected(($filters['verified'] ?? 'all') === 'all')>All</option>
                    <option value="yes" @selected(($filters['verified'] ?? '') === 'yes')>Verified only</option>
                    <option value="no" @selected(($filters['verified'] ?? '') === 'no')>Not verified</option>
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Subscription</label>
                <select class="ui-input w-auto py-1.5 text-sm" name="subscription">
                    <option value="all" @selected(($filters['subscription'] ?? 'all') === 'all')>Any</option>
                    <option value="active" @selected(($filters['subscription'] ?? '') === 'active')>Active</option>
                    <option value="expired" @selected(($filters['subscription'] ?? '') === 'expired')>Expired</option>
                    <option value="none" @selected(($filters['subscription'] ?? '') === 'none')>No active plan</option>
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Plan</label>
                <select class="ui-input w-auto max-w-[12rem] py-1.5 text-sm" name="plan_id">
                    <option value="">All plans</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label text-xs">Tool</label>
                <select class="ui-input w-auto max-w-[12rem] py-1.5 text-sm" name="tool_id">
                    <option value="">All tools</option>
                    @foreach ($tools as $tool)
                        <option value="{{ $tool->id }}" @selected((string) ($filters['tool_id'] ?? '') === (string) $tool->id)>{{ $tool->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="ui-btn-primary text-sm">Apply filters</button>
            <a href="{{ route('admin.users') }}" class="ui-btn-outline text-sm">Clear</a>
        </div>
    </form>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Sub</th><th>Status</th><th>Verified</th><th>Joined</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="font-medium">
                            <a href="{{ route('admin.users.show', $u['id']) }}" class="text-accent hover:underline">{{ $u['name'] }}</a>
                        </td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['plan'] }}</td>
                        <td>
                            <span @class([
                                'text-xs',
                                'text-success' => $u['subscription_status'] === 'Active',
                                'text-ink-muted' => $u['subscription_status'] === 'None',
                                'text-warning' => $u['subscription_status'] === 'Expired',
                            ])>{{ $u['subscription_status'] }}</span>
                        </td>
                        <td>
                            <span @class([
                                'dash-badge-online' => $u['status'] === 'Active',
                                'dash-badge-offline' => $u['status'] === 'Blocked',
                            ])>{{ $u['status'] }}</span>
                        </td>
                        <td>
                            @if ($u['verified'])
                                <span class="text-xs text-success">Yes</span>
                            @else
                                <span class="text-xs text-warning">No</span>
                            @endif
                        </td>
                        <td>{{ $u['joined'] }}</td>
                        <td>
                            <a href="{{ route('admin.users.show', $u['id']) }}" class="ui-btn-ghost text-xs">Profile</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-ink-muted">No users match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$users" :per-page="$perPage" />
    </div>
@endsection
