@extends('layouts.reseller')

@section('title', 'My users')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="dash-page-title">My users</h1>
            <p class="text-sm text-ink-secondary">Users you created or provisioned.</p>
        </div>
        <a href="{{ route('reseller.password.create') }}" class="ui-btn-ghost text-xs">Reset password</a>
    </div>

    <x-list-filters
        search="Email or name…"
        :search-value="$filters['q'] ?? ''"
        :clear-url="route('reseller.users.index')"
        :filters="[
            [
                'name' => 'tool_id',
                'label' => 'Tool / Plan',
                'type' => 'select',
                'value' => $filters['tool_id'] ?? '',
                'options' => ['' => 'All'] + $tools->pluck('name', 'id')->all(),
            ],
            ['name' => 'from', 'label' => 'Joined from', 'type' => 'date', 'value' => $filters['from'] ?? ''],
            ['name' => 'to', 'label' => 'Joined to', 'type' => 'date', 'value' => $filters['to'] ?? ''],
        ]"
    />

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Last plan</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $last = $lastProvisions->get($user->id);
                        $active = $user->subscriptions->first();
                    @endphp
                    <tr>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $active?->tool?->name ?? $last?->tool?->name ?? '—' }}</td>
                        <td class="text-sm text-ink-secondary">
                            {{ $last?->created_at?->format('M j, Y') ?? $user->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            <a href="{{ route('reseller.password.create', ['email' => $user->email]) }}" class="ui-btn-ghost text-xs">Reset password</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-ink-muted">No users match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$users" :per-page="$perPage" />
    </div>
@endsection
