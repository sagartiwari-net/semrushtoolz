@extends('layouts.reseller')

@section('title', 'Balance')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Balance</h1>
        <p class="text-sm text-ink-secondary">Prepaid INR balance. Top up with UPI, or request admin credit for cash / manual payments.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <div class="ui-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Current balance</p>
            <p class="mt-2 text-3xl font-bold text-ink">₹{{ number_format($balance, 2) }}</p>
        </div>

        <div class="ui-card p-5">
            <h2 class="mb-3 text-base font-bold text-ink">Pay with UPI</h2>
            @if (empty($paymentMethods))
                <p class="text-sm text-warning">UPI is not configured yet. Use <strong>Request admin top-up</strong> below for cash / manual credit.</p>
            @else
                <form method="POST" action="{{ route('reseller.balance.pay') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">Amount to credit (INR)</label>
                        <input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required class="ui-input w-full" placeholder="e.g. 5000">
                    </div>
                    <input type="hidden" name="payment_method" value="upi">
                    <p class="text-xs text-ink-muted">Auto-verified QR payment — balance credits instantly after payment.</p>
                    <button type="submit" class="ui-btn-primary">Continue to UPI</button>
                </form>
            @endif
        </div>
    </div>

    <div class="mb-6 ui-card p-5">
        <h2 class="mb-1 text-base font-bold text-ink">Request admin top-up (cash / manual)</h2>
        <p class="mb-4 text-sm text-ink-secondary">
            Use this when you pay admin in cash or offline. Admin gets the request under
            <strong>Resellers → Balance requests</strong> and can approve to credit your balance.
        </p>
        @if ($hasPending)
            <p class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning">
                You already have a pending request. Wait for admin approval, or check the Request history tab.
            </p>
        @else
            <form method="POST" action="{{ route('reseller.balance.request') }}" class="grid gap-3 sm:grid-cols-3 sm:items-end">
                @csrf
                <div class="sm:col-span-1">
                    <label class="mb-1 block text-sm font-medium text-ink">Amount (INR)</label>
                    <input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required class="ui-input w-full" placeholder="e.g. 10000">
                </div>
                <div class="sm:col-span-1">
                    <label class="mb-1 block text-sm font-medium text-ink">Note (optional)</label>
                    <input type="text" name="note" value="{{ old('note') }}" class="ui-input w-full" placeholder="Cash paid to admin / txn id">
                </div>
                <button type="submit" class="ui-btn-primary">Submit for admin approval</button>
            </form>
        @endif
    </div>

    <x-dash-tabs
        :tabs="['ledger' => 'Ledger', 'requests' => 'Request history']"
        :active="$tab"
        :preserve="['q', 'type', 'request_status', 'from', 'to', 'per_page']"
    >
        @if ($tab === 'ledger')
            <x-list-filters
                search="Type or note…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('reseller.balance.index', ['tab' => 'ledger'])"
                :preserve="['tab' => 'ledger']"
                :filters="[
                    [
                        'name' => 'type',
                        'label' => 'Ledger type',
                        'type' => 'select',
                        'value' => $filters['type'] ?? 'all',
                        'options' => [
                            'all' => 'All types',
                            'credit_admin' => 'Admin credit',
                            'credit_request' => 'Request credit',
                            'credit_payment' => 'UPI credit',
                            'debit_provision' => 'Provision debit',
                            'debit_adjustment' => 'Adjustment',
                            'credit_cancel_refund' => 'Cancel refund',
                        ],
                    ],
                    ['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $filters['from'] ?? ''],
                    ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $filters['to'] ?? ''],
                ]"
            />

            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance after</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ledger as $row)
                            <tr>
                                <td class="font-mono text-xs">{{ $row->type }}</td>
                                <td @class(['text-success' => $row->amount_inr > 0, 'text-danger' => $row->amount_inr < 0])>
                                    ₹{{ number_format((float) $row->amount_inr, 2) }}
                                </td>
                                <td>₹{{ number_format((float) $row->balance_after, 2) }}</td>
                                <td class="text-sm text-ink-secondary">{{ $row->created_at->format('M j, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-ink-muted">No ledger entries.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$ledger" :per-page="$perPage" />
            </div>
        @else
            <x-list-filters
                search=""
                :search="false"
                :clear-url="route('reseller.balance.index', ['tab' => 'requests'])"
                :preserve="['tab' => 'requests']"
                :filters="[
                    [
                        'name' => 'request_status',
                        'label' => 'Status',
                        'type' => 'select',
                        'value' => $filters['request_status'] ?? 'all',
                        'options' => [
                            'all' => 'All',
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ],
                    ],
                    ['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $filters['from'] ?? ''],
                    ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $filters['to'] ?? ''],
                ]"
            />

            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Note</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr>
                                <td>₹{{ number_format((float) $req->amount_inr, 2) }}</td>
                                <td class="max-w-xs text-sm text-ink-secondary">{{ $req->note ?: '—' }}</td>
                                <td>
                                    <span @class([
                                        'dash-badge-online' => $req->status === 'approved',
                                        'dash-badge-offline' => $req->status === 'rejected',
                                        'text-warning' => $req->status === 'pending',
                                    ])>{{ ucfirst($req->status) }}</span>
                                </td>
                                <td class="text-sm text-ink-secondary">{{ $req->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-ink-muted">No requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$requests" :per-page="$perPage" />
            </div>
        @endif
    </x-dash-tabs>
@endsection
