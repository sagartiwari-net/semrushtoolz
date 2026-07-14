@extends('layouts.admin')

@section('title', $reseller->name)

@section('content')
    <x-admin.reseller-nav />

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="dash-page-title !mb-1">{{ $reseller->name }}</h1>
            <p class="text-sm text-ink-secondary">{{ $reseller->email }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.resellers.edit', $reseller) }}" class="ui-btn-ghost text-xs">Edit account</a>
            <form method="POST" action="{{ route('admin.resellers.destroy', $reseller) }}"
                  onsubmit="return confirm('Permanently delete reseller {{ $reseller->email }}?\n\nBalance, ledger, and provision history will be removed. End-user accounts stay.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-btn-ghost text-xs text-danger">Delete</button>
            </form>
        </div>
    </div>

    <x-dash-tabs
        :tabs="[
            'overview' => 'Overview',
            'pricing' => 'Pricing',
            'provisions' => 'Provisions',
            'cancelled' => 'Cancelled',
            'ledger' => 'Ledger',
            'requests' => 'Requests',
        ]"
        :active="$tab"
        :preserve="['q', 'type', 'flag', 'tool_id', 'status', 'sellable_type', 'from', 'to', 'per_page']"
    >
        @if ($tab === 'overview')
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="ui-card p-4">
                    <p class="text-xs text-ink-muted">Balance</p>
                    <p class="mt-1 text-2xl font-bold">₹{{ number_format((float) ($reseller->resellerBalance?->balance_inr ?? 0), 2) }}</p>
                </div>
                <div class="ui-card p-4">
                    <p class="text-xs text-ink-muted">Profile</p>
                    <p class="mt-1 font-semibold">{{ $reseller->resellerProfile?->is_active ? 'Active' : 'Inactive' }}</p>
                </div>
                <div class="ui-card p-4">
                    <p class="text-xs text-ink-muted">User status</p>
                    <p class="mt-1 font-semibold">{{ ucfirst($reseller->status) }}</p>
                </div>
            </div>

            <div class="mb-6 ui-card p-4">
                <p class="text-xs text-ink-muted">Monthly cancel limit</p>
                <p class="mt-1 font-semibold">
                    @if ($reseller->resellerProfile?->canCancelAccess())
                        {{ $reseller->resellerProfile->monthly_cancel_limit }} cancellations / calendar month
                    @else
                        Disabled (reseller cannot cancel)
                    @endif
                </p>
                <p class="mt-1 text-xs text-ink-muted">Reseller window: within 1 hour of provision. Admin can cancel anytime; refund always returns to this reseller.</p>
                <a href="{{ route('admin.resellers.edit', $reseller) }}" class="mt-2 inline-block text-xs text-accent hover:underline">Edit limit</a>
            </div>

            @if ($reseller->resellerProfile?->notes)
                <div class="mb-6 ui-card p-4">
                    <p class="text-xs text-ink-muted">Notes</p>
                    <p class="mt-1 text-sm text-ink-secondary whitespace-pre-wrap">{{ $reseller->resellerProfile->notes }}</p>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('admin.resellers.credit', $reseller) }}" class="ui-card space-y-3 p-5">
                    @csrf
                    <h2 class="text-base font-bold">Add balance</h2>
                    <div>
                        <label class="mb-1 block text-sm">Amount (INR)</label>
                        <input type="number" name="amount" min="1" step="1" required class="ui-input w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm">Note</label>
                        <input type="text" name="note" class="ui-input w-full">
                    </div>
                    <button type="submit" class="ui-btn-primary text-xs">Credit balance</button>
                </form>

                <div class="ui-card p-5">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2 class="text-base font-bold">Recent requests</h2>
                        <a href="{{ route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'requests']) }}" class="text-xs text-accent hover:underline">View all</a>
                    </div>
                    <div class="space-y-2 text-sm">
                        @forelse ($requests as $req)
                            <div class="flex justify-between gap-2 border-b border-line py-2">
                                <span>₹{{ number_format((float) $req->amount_inr, 2) }} — {{ $req->status }}</span>
                                <span class="text-ink-muted">{{ $req->created_at->format('M j') }}</span>
                            </div>
                        @empty
                            <p class="text-ink-muted">No requests.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        @elseif ($tab === 'pricing')
            <p class="mb-4 text-sm text-ink-secondary">Leave blank to use the global default. Tools with no default and no override stay hidden from this reseller.</p>

            <x-list-filters
                search="Tool name or slug…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'pricing'])"
                :preserve="['tab' => 'pricing']"
                :filters="[
                    [
                        'name' => 'sellable_type',
                        'label' => 'Type',
                        'type' => 'select',
                        'value' => $filters['sellable_type'] ?? 'all',
                        'options' => [
                            'all' => 'All types',
                            'individual' => 'Individual',
                            'plan' => 'Plan',
                        ],
                    ],
                ]"
            />

            <form method="POST" action="{{ route('admin.resellers.tool-pricing.update', $reseller) }}">
                @csrf
                @foreach (request()->only(['q', 'sellable_type', 'per_page', 'page']) as $key => $value)
                    @if ($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="hidden" name="tab" value="pricing">

                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Tool</th>
                                <th>Default</th>
                                <th>Override (INR / month)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tools as $tool)
                                <tr>
                                    <td>{{ $tool->name }}</td>
                                    <td class="text-ink-secondary">
                                        {{ isset($defaults[$tool->id]) ? '₹'.$defaults[$tool->id] : '—' }}
                                    </td>
                                    <td>
                                        <input type="number" name="prices[{{ $tool->id }}]" min="0" step="1"
                                               value="{{ old('prices.'.$tool->id, $overrides[$tool->id] ?? '') }}"
                                               class="ui-input w-32" placeholder="default">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-ink-muted">No tools match.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <x-dash-pagination :paginator="$tools" :per-page="$perPage" />
                </div>
                @if ($tools->count() > 0)
                    <button type="submit" class="ui-btn-primary mt-4 text-xs">Save overrides</button>
                @endif
            </form>

        @elseif ($tab === 'provisions')
            <x-list-filters
                search="End-user email…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'provisions'])"
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
                        'label' => 'Flag',
                        'type' => 'select',
                        'value' => $filters['flag'] ?? 'all',
                        'options' => [
                            'all' => 'All',
                            'new' => 'New user',
                            'existing' => 'Existing',
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
                                    @elseif ($row->was_new_user) New
                                    @else Existing @endif
                                </td>
                                <td class="text-sm text-ink-secondary">{{ $row->created_at->format('M j, Y H:i') }}</td>
                                <td class="whitespace-nowrap">
                                    @if (! $row->password_reset && (float) $row->amount_charged > 0)
                                        <form method="POST" action="{{ route('admin.resellers.provisions.cancel', [$reseller, $row]) }}"
                                              onsubmit="return confirm('Cancel this access and refund ₹{{ number_format((float) $row->amount_charged, 2) }} to the reseller?')">
                                            @csrf
                                            <button type="submit" class="ui-btn-ghost text-xs text-danger">Cancel + refund</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-ink-muted">No active provisions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$provisions" :per-page="$perPage" />
            </div>

        @elseif ($tab === 'cancelled')
            <x-list-filters
                search="End-user email…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'cancelled'])"
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
                            <tr><td colspan="8" class="text-center text-ink-muted">No cancelled plans.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$provisions" :per-page="$perPage" />
            </div>

        @elseif ($tab === 'ledger')
            <x-list-filters
                search="Type or note…"
                :search-value="$filters['q'] ?? ''"
                :clear-url="route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'ledger'])"
                :preserve="['tab' => 'ledger']"
                :filters="[
                    [
                        'name' => 'type',
                        'label' => 'Type',
                        'type' => 'select',
                        'value' => $filters['type'] ?? 'all',
                        'options' => [
                            'all' => 'All types',
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

        @else
            <x-list-filters
                :search="false"
                :clear-url="route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'requests'])"
                :preserve="['tab' => 'requests']"
                :filters="[
                    [
                        'name' => 'status',
                        'label' => 'Status',
                        'type' => 'select',
                        'value' => $filters['status'] ?? 'all',
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
                            <th>Status</th>
                            <th>Note</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr>
                                <td>₹{{ number_format((float) $req->amount_inr, 2) }}</td>
                                <td>{{ ucfirst($req->status) }}</td>
                                <td class="text-sm text-ink-secondary">{{ $req->note ?: '—' }}</td>
                                <td class="text-sm text-ink-secondary">{{ $req->created_at->format('M j, Y H:i') }}</td>
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
