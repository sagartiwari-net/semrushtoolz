@extends('layouts.dashboard')

@section('title', 'Wallet')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="dash-page-title">SemrushToolz Wallet</h1>
            <p class="mt-1 text-sm text-ink-muted">Use wallet balance for subscriptions. Top up or transfer affiliate earnings.</p>
        </div>
        <a href="{{ route('dashboard.wallet.topup') }}" class="ui-btn-primary">Add Money</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="dash-stats-grid mb-6">
        <x-dashboard.stat-card label="Available Balance" :value="$balanceLabel" sub="INR only · subscription purchases" icon="green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Cashback on purchases" :value="(int) ($config['purchase_cashback_percent'] * 100).'%'" sub="UPI / PayPal / offline only" icon="blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </x-dashboard.stat-card>
    </div>

    <div class="dash-card">
        <h2 class="dash-card-title">Transaction History</h2>
        <div class="dash-table-wrap border-0 shadow-none">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                            <td>{{ $tx->typeLabel() }}</td>
                            <td class="max-w-xs truncate">{{ $tx->description ?? '—' }}</td>
                            <td @class(['font-semibold', 'text-success' => $tx->isCredit(), 'text-danger' => ! $tx->isCredit()])>
                                {{ $tx->isCredit() ? '+' : '' }}₹{{ number_format(abs((float) $tx->amount), 2) }}
                            </td>
                            <td>₹{{ number_format((float) $tx->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-ink-muted">No wallet activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <x-dash-pagination :paginator="$transactions" :per-page="15" />
        </div>
    </div>
@endsection
