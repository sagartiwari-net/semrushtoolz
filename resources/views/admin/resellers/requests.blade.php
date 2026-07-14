@extends('layouts.admin')

@section('title', 'Balance requests')

@section('content')
    <x-admin.reseller-nav />

    <div class="mb-5">
        <h1 class="dash-page-title !mb-1">Reseller balance requests</h1>
        <p class="text-sm text-ink-secondary">Cash / offline top-ups from resellers. Approve to credit INR balance; reject with an optional note.</p>
    </div>

    <x-list-filters
        search="Reseller name or email…"
        :search-value="$filters['q'] ?? ''"
        :clear-url="route('admin.resellers.requests')"
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
            [
                'name' => 'reseller_id',
                'label' => 'Reseller',
                'type' => 'select',
                'value' => $filters['reseller_id'] ?? '',
                'options' => ['' => 'All resellers'] + $resellers->mapWithKeys(fn ($r) => [$r->id => $r->name.' ('.$r->email.')'])->all(),
            ],
            ['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => $filters['from'] ?? ''],
            ['name' => 'to', 'label' => 'To', 'type' => 'date', 'value' => $filters['to'] ?? ''],
        ]"
    />

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Reseller</th>
                    <th>Amount</th>
                    <th>Note</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $req)
                    <tr>
                        <td>
                            <a href="{{ route('admin.resellers.show', $req->reseller_user_id) }}" class="font-semibold text-ink hover:underline">
                                {{ $req->reseller?->name ?? '—' }}
                            </a>
                            <div class="text-xs text-ink-muted">{{ $req->reseller?->email }}</div>
                        </td>
                        <td>₹{{ number_format((float) $req->amount_inr, 2) }}</td>
                        <td class="max-w-xs text-sm text-ink-secondary">{{ $req->note ?: '—' }}</td>
                        <td>{{ ucfirst($req->status) }}</td>
                        <td class="text-sm text-ink-secondary">{{ $req->created_at->format('M j, Y H:i') }}</td>
                        <td class="whitespace-nowrap">
                            @if ($req->isPending())
                                <form method="POST" action="{{ route('admin.resellers.requests.approve', $req) }}" class="mb-2 inline-flex flex-col gap-1">
                                    @csrf
                                    <input type="text" name="admin_note" placeholder="Admin note" class="ui-input text-xs">
                                    <button type="submit" class="ui-btn-primary text-xs">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.resellers.requests.reject', $req) }}" class="inline-flex flex-col gap-1">
                                    @csrf
                                    <input type="text" name="admin_note" placeholder="Reject reason" class="ui-input text-xs">
                                    <button type="submit" class="ui-btn-ghost text-xs text-danger">Reject</button>
                                </form>
                            @else
                                <span class="text-xs text-ink-muted">{{ $req->admin_note ?: 'Reviewed' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-ink-muted">No requests.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$requests" :per-page="$perPage" />
    </div>
@endsection
