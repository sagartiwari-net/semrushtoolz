@extends('layouts.admin')

@section('title', 'Resellers')

@section('content')
    <x-admin.reseller-nav :pending-requests="$pendingRequests" />

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="dash-page-title !mb-1">Resellers</h1>
            <p class="text-sm text-ink-secondary">Prepaid reseller accounts and balance management.</p>
        </div>
        <a href="{{ route('admin.resellers.create') }}" class="ui-btn-primary text-xs">+ Create reseller</a>
    </div>

    <x-list-filters
        search="Name or email…"
        :search-value="$filters['q'] ?? ''"
        :clear-url="route('admin.resellers.index')"
        :filters="[
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'value' => $filters['status'] ?? 'all',
                'options' => [
                    'all' => 'All',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
            ],
        ]"
    />

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($resellers as $r)
                    <tr>
                        <td class="font-semibold text-ink">{{ $r->name }}</td>
                        <td>{{ $r->email }}</td>
                        <td>₹{{ number_format((float) ($r->resellerBalance?->balance_inr ?? 0), 2) }}</td>
                        <td>
                            <span @class([
                                'dash-badge-online' => $r->resellerProfile?->is_active && $r->status === 'active',
                                'dash-badge-offline' => ! ($r->resellerProfile?->is_active && $r->status === 'active'),
                            ])>
                                {{ ($r->resellerProfile?->is_active && $r->status === 'active') ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap space-x-2">
                            <a href="{{ route('admin.resellers.show', $r) }}" class="ui-btn-ghost text-xs">View</a>
                            <a href="{{ route('admin.resellers.edit', $r) }}" class="ui-btn-ghost text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.resellers.destroy', $r) }}" class="inline"
                                  onsubmit="return confirm('Delete reseller {{ $r->email }}?\n\nThis removes their balance, ledger, pricing, and provision history. End-user accounts they created will remain.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-btn-ghost text-xs text-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-ink-muted">No resellers match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$resellers" :per-page="$perPage" />
    </div>
@endsection
