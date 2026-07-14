@extends('layouts.reseller')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Dashboard</h1>
        <p class="text-sm text-ink-secondary">Your prepaid balance and recent activity.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="ui-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Balance</p>
            <p class="mt-2 text-3xl font-bold text-ink">₹{{ number_format($balance, 2) }}</p>
        </div>
        <div class="ui-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Pending top-up requests</p>
            <p class="mt-2 text-3xl font-bold text-ink">{{ $pendingRequests }}</p>
            <a href="{{ route('reseller.balance.index') }}" class="mt-3 inline-block text-sm text-accent">View balance →</a>
        </div>
        <div class="ui-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Cancels this month</p>
            @if ($cancelLimit === null)
                <p class="mt-2 text-lg font-bold text-ink-muted">Disabled</p>
                <p class="mt-1 text-xs text-ink-muted">Ask admin to enable a monthly limit.</p>
            @else
                <p class="mt-2 text-3xl font-bold text-ink">{{ $cancelsRemaining }} <span class="text-base font-medium text-ink-muted">/ {{ $cancelLimit }} left</span></p>
                <p class="mt-1 text-xs text-ink-muted">Only within 1 hour of provision.</p>
            @endif
        </div>
    </div>

    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-base font-bold text-ink">Recent provisions</h2>
        <div class="flex items-center gap-2">
            <a href="{{ route('reseller.reports.index', ['tab' => 'cancelled']) }}" class="ui-btn-ghost text-xs">Cancelled report</a>
            <a href="{{ route('reseller.provision.create') }}" class="ui-btn-primary text-xs">Provision access</a>
        </div>
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Tool / Plan</th>
                    <th>Months</th>
                    <th>Charged</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentProvisions as $row)
                    <tr>
                        <td>{{ $row->end_user_email }}</td>
                        <td>{{ $row->tool?->name ?? '—' }}</td>
                        <td>{{ $row->duration_months }}</td>
                        <td>₹{{ number_format((float) $row->amount_charged, 2) }}</td>
                        <td>
                            @if ($row->isCancelled()) Cancelled
                            @elseif ($row->was_new_user) New user
                            @else Existing @endif
                        </td>
                        <td class="text-sm text-ink-secondary">{{ $row->created_at->format('M j, Y H:i') }}</td>
                        <td>
                            @if ($cancels->resellerCanCancel(auth()->user(), $row))
                                <form method="POST" action="{{ route('reseller.provisions.cancel', $row) }}"
                                      onsubmit="return confirm('Cancel this access and refund ₹{{ number_format((float) $row->amount_charged, 2) }}?')">
                                    @csrf
                                    <button type="submit" class="ui-btn-ghost text-xs text-danger">Cancel</button>
                                </form>
                            @elseif ($row->isCancelled())
                                <a href="{{ route('reseller.reports.index', ['tab' => 'cancelled']) }}" class="text-xs text-accent hover:underline">In Cancelled</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-ink-muted">No provisions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
