@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select class="ui-input w-auto" name="status" onchange="this.form.submit()">
            <option value="all" @selected(request('status', 'all') === 'all')>All status</option>
            <option value="open" @selected(request('status') === 'open')>Open</option>
            <option value="replied" @selected(request('status') === 'replied')>Replied</option>
            <option value="closed" @selected(request('status') === 'closed')>Closed</option>
        </select>
        <select class="ui-input w-auto" name="priority" onchange="this.form.submit()">
            <option value="all" @selected(request('priority', 'all') === 'all')>All priority</option>
            <option value="high" @selected(request('priority') === 'high')>High</option>
            <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
            <option value="low" @selected(request('priority') === 'low')>Low</option>
        </select>
    </form>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Ticket</th><th>User</th><th>Subject</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="font-medium text-ink">{{ $ticket['id'] }}</td>
                        <td>{{ $ticket['user'] }}</td>
                        <td>{{ $ticket['subject'] }}</td>
                        <td><span @class(['text-danger font-semibold' => $ticket['priority'] === 'High'])>{{ $ticket['priority'] }}</span></td>
                        <td><span @class(['dash-badge-pending' => $ticket['status'] === 'Open', 'dash-badge-online' => $ticket['status'] === 'Replied', 'dash-badge-offline' => $ticket['status'] === 'Closed'])>{{ $ticket['status'] }}</span></td>
                        <td>{{ $ticket['date'] }}</td>
                        <td><a href="{{ route('admin.tickets.show', $ticket['ticket_id']) }}" class="ui-btn-ghost text-xs">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-ink-muted">No tickets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
