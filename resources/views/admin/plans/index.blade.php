@extends('layouts.admin')

@section('title', 'Plans')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-secondary">Create plans and choose which tools each plan unlocks. Click subscriber counts to filter users.</p>
        <a href="{{ route('admin.plans.create') }}" class="ui-btn-primary">+ Add Plan</a>
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>ID</th>
                    <th>Group</th>
                    <th>Tools</th>
                    <th>INR</th>
                    <th>USD</th>
                    <th>Subscribers</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    @php $counts = $subscriberCounts[$plan->id] ?? ['active' => 0, 'expired' => 0, 'total' => 0]; @endphp
                    <tr>
                        <td class="font-medium text-ink">
                            {{ $plan->name }}
                            @if ($plan->is_trial)
                                <span class="ui-badge ml-1 bg-purple/10 text-purple">Trial</span>
                            @endif
                        </td>
                        <td><code class="text-xs">#{{ $plan->id }}</code></td>
                        <td><span class="ui-badge bg-surface text-ink-secondary">{{ $plan->display_group }}</span></td>
                        <td class="text-xs text-ink-muted">{{ $plan->tools->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>₹{{ number_format($plan->price_inr) }}</td>
                        <td>${{ $plan->price_usd }}</td>
                        <td class="text-xs whitespace-nowrap">
                            <a href="{{ route('admin.users', ['plan_id' => $plan->id, 'subscription' => 'active']) }}" class="text-success hover:underline" title="Active subscribers">{{ $counts['active'] }} active</a>
                            <span class="text-ink-muted"> · </span>
                            <a href="{{ route('admin.users', ['plan_id' => $plan->id, 'subscription' => 'expired']) }}" class="text-warning hover:underline" title="Expired">{{ $counts['expired'] }} expired</a>
                            <span class="text-ink-muted"> · </span>
                            <a href="{{ route('admin.users', ['plan_id' => $plan->id]) }}" class="text-accent hover:underline" title="All time">{{ $counts['total'] }} total</a>
                        </td>
                        <td>
                            <span @class(['dash-badge-online' => $plan->is_active, 'dash-badge-offline' => !$plan->is_active])>
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="ui-btn-ghost text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}" class="inline">@csrf
                                <button class="ui-btn-ghost text-xs">{{ $plan->is_active ? 'Off' : 'On' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
