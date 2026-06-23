@extends('layouts.admin')

@section('title', 'Wallet Transactions')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="dash-page-title">Wallet Transactions</h1>
            <p class="mt-1 text-sm text-ink-muted">Ledger of all wallet credits and debits across users.</p>
        </div>
        <a href="{{ route('admin.settings') }}#wallet" class="ui-btn-outline text-sm">Wallet Settings</a>
        <a href="{{ route('admin.wallet.export', request()->only(['q', 'type'])) }}" class="ui-btn-outline text-sm">Export CSV</a>
        <a href="{{ route('dashboard.wallet') }}" target="_blank" class="ui-btn-outline text-sm">User Wallet Page</a>
    </div>

    @if (! $walletEnabled)
        <div class="mb-4 rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning">
            Wallet is currently <strong>disabled</strong> for users. You can still view history; enable it in Settings to allow top-ups and payments.
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.wallet.adjust') }}" class="dash-card space-y-4 lg:col-span-1">
            @csrf
            <h2 class="dash-card-title">Manual Adjustment</h2>
            <p class="text-xs text-ink-muted">Use positive amount to credit, negative to debit. Recorded in the ledger.</p>
            <div>
                <label class="ui-label">User email</label>
                <input class="ui-input" type="email" name="user_email" value="{{ old('user_email') }}" required>
            </div>
            <div>
                <label class="ui-label">Amount (₹)</label>
                <input class="ui-input" type="number" name="amount" step="0.01" value="{{ old('amount') }}" required>
            </div>
            <div>
                <label class="ui-label">Note</label>
                <input class="ui-input" name="note" value="{{ old('note') }}" placeholder="Reason for adjustment" required>
            </div>
            <button type="submit" class="ui-btn-primary w-full">Apply Adjustment</button>
        </form>

        <div class="dash-card lg:col-span-2">
            <form method="GET" class="mb-4 flex flex-wrap gap-2">
                <input class="ui-input min-w-[12rem] flex-1" name="q" value="{{ $filters['q'] }}" placeholder="Search user or description">
                <select class="ui-input w-auto" name="type">
                    <option value="">All types</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn-outline">Filter</button>
            </form>

            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="whitespace-nowrap">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <div class="font-medium text-ink">{{ $tx->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-ink-muted">{{ $tx->user?->email }}</div>
                                </td>
                                <td>{{ $tx->typeLabel() }}</td>
                                <td @class(['font-semibold', 'text-success' => $tx->isCredit(), 'text-danger' => ! $tx->isCredit()])>
                                    {{ $tx->isCredit() ? '+' : '' }}₹{{ number_format(abs((float) $tx->amount), 2) }}
                                </td>
                                <td>₹{{ number_format((float) $tx->balance_after, 2) }}</td>
                                <td class="max-w-xs truncate">{{ $tx->description ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-ink-muted">No wallet transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$transactions" :per-page="20" />
            </div>
        </div>
    </div>
@endsection
