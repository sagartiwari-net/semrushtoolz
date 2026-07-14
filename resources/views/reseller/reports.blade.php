@extends('layouts.reseller')

@section('title', 'Reports')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Reports</h1>
        <p class="text-sm text-ink-secondary">Active access, cancelled plans, and balance history. Cancelled rows stay in the Cancelled tab permanently.</p>
        @if ($cancelLimit === null)
            <p class="mt-2 text-sm text-ink-muted">Cancel access is disabled for your account. Ask admin to set a monthly cancel limit.</p>
        @else
            <p class="mt-2 text-sm text-ink-secondary">Cancels left this month: <strong>{{ $cancelsRemaining }}</strong> / {{ $cancelLimit }} (only within 1 hour of provision).</p>
        @endif
    </div>

    <x-dash-tabs
        :tabs="['provisions' => 'Access report', 'cancelled' => 'Cancelled', 'ledger' => 'Balance history']"
        :active="$tab"
        :preserve="['q', 'tool_id', 'flag', 'type', 'from', 'to', 'per_page']"
    >
        @if ($tab === 'provisions')
            <x-list-filters
                search="End-user email…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('reseller.reports.index', ['tab' => 'provisions'])"
                :preserve="['tab' => 'provisions']"
                :filters="[
                    [
                        'name' => 'tool_id',
                        'label' => 'Tool',
                        'type' => 'select',
                        'value' => $filters['tool_id'] ?? '',
                        'options' => ['' => 'All tools'] + $tools->pluck('name', 'id')->all(),
                    ],
                    [
                        'name' => 'flag',
                        'label' => 'Provision flag',
                        'type' => 'select',
                        'value' => $filters['flag'] ?? 'all',
                        'options' => [
                            'all' => 'All',
                            'new' => 'New user',
                            'existing' => 'Existing user',
                            'password_reset' => 'Password reset',
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
                            <th>Email</th>
                            <th>Tool</th>
                            <th>Months</th>
                            <th>Charged</th>
                            <th>Flags</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($provisions as $row)
                            <tr>
                                <td>{{ $row->end_user_email }}</td>
                                <td>{{ $row->tool?->name ?? '—' }}</td>
                                <td>{{ $row->duration_months }}</td>
                                <td>₹{{ number_format((float) $row->amount_charged, 2) }}</td>
                                <td class="text-xs">
                                    @if ($row->password_reset) Password reset
                                    @elseif ($row->was_new_user) New user
                                    @else Existing @endif
                                </td>
                                <td class="text-sm text-ink-secondary">{{ $row->created_at->format('M j, Y H:i') }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($cancels->resellerCanCancel(auth()->user(), $row))
                                        <form method="POST" action="{{ route('reseller.provisions.cancel', $row) }}"
                                              onsubmit="return confirm('Cancel this access and refund ₹{{ number_format((float) $row->amount_charged, 2) }} to your balance?')">
                                            @csrf
                                            <button type="submit" class="ui-btn-ghost text-xs text-danger">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-ink-muted">No active provisions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$provisions" :per-page="$perPage" />
            </div>

        @elseif ($tab === 'cancelled')
            <x-list-filters
                search="End-user email…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('reseller.reports.index', ['tab' => 'cancelled'])"
                :preserve="['tab' => 'cancelled']"
                :filters="[
                    [
                        'name' => 'tool_id',
                        'label' => 'Tool',
                        'type' => 'select',
                        'value' => $filters['tool_id'] ?? '',
                        'options' => ['' => 'All tools'] + $tools->pluck('name', 'id')->all(),
                    ],
                    ['name' => 'from', 'label' => 'Cancelled from', 'type' => 'date', 'value' => $filters['from'] ?? ''],
                    ['name' => 'to', 'label' => 'Cancelled to', 'type' => 'date', 'value' => $filters['to'] ?? ''],
                ]"
            />

            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Tool</th>
                            <th>Months</th>
                            <th>Charged</th>
                            <th>Refund</th>
                            <th>Cancelled by</th>
                            <th>Provisioned</th>
                            <th>Cancelled</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($provisions as $row)
                            <tr>
                                <td>{{ $row->end_user_email }}</td>
                                <td>{{ $row->tool?->name ?? '—' }}</td>
                                <td>{{ $row->duration_months }}</td>
                                <td>₹{{ number_format((float) $row->amount_charged, 2) }}</td>
                                <td>₹{{ number_format((float) ($row->refund_amount ?? 0), 2) }}</td>
                                <td class="text-xs">{{ ucfirst((string) $row->cancelled_by_role) }}</td>
                                <td class="text-sm text-ink-secondary">{{ $row->created_at->format('M j, Y H:i') }}</td>
                                <td class="text-sm text-ink-secondary">{{ $row->cancelled_at?->format('M j, Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-ink-muted">No cancelled plans yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$provisions" :per-page="$perPage" />
            </div>

        @else
            <x-list-filters
                search="Type or note…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('reseller.reports.index', ['tab' => 'ledger'])"
                :preserve="['tab' => 'ledger']"
                :filters="[
                    [
                        'name' => 'type',
                        'label' => 'Ledger type',
                        'type' => 'select',
                        'value' => $filters['type'] ?? 'all',
                        'options' => [
                            'all' => 'All ledger types',
                            'credit_admin' => 'Admin credit',
                            'credit_request' => 'Request credit',
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
                                <td>₹{{ number_format((float) $row->amount_inr, 2) }}</td>
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
        @endif
    </x-dash-tabs>
@endsection
