@extends('layouts.admin')

@section('title', 'Reseller reports')

@section('content')
    <x-admin.reseller-nav />

    <div class="mb-5">
        <h1 class="dash-page-title !mb-1">Reseller provision reports</h1>
        <p class="text-sm text-ink-secondary">Filter by reseller, tool, flag, and date. Cancelled plans stay in this report.</p>
    </div>

    <x-list-filters
        search="End-user email…"
        :search-value="$filters['q'] ?? ''"
        :clear-url="route('admin.resellers.reports')"
        :filters="[
            [
                'name' => 'reseller_id',
                'label' => 'Reseller',
                'type' => 'select',
                'value' => $filters['reseller_id'] ?? '',
                'options' => ['' => 'All'] + $resellers->mapWithKeys(fn ($r) => [$r->id => $r->name])->all(),
            ],
            [
                'name' => 'tool_id',
                'label' => 'Tool',
                'type' => 'select',
                'value' => $filters['tool_id'] ?? '',
                'options' => ['' => 'All'] + $tools->pluck('name', 'id')->all(),
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
                    'cancelled' => 'Cancelled',
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
                    <th>Reseller</th>
                    <th>End user</th>
                    <th>Tool</th>
                    <th>Months</th>
                    <th>Charged</th>
                    <th>Flags</th>
                    <th>Refund</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($provisions as $row)
                    <tr @class(['opacity-70' => $row->isCancelled()])>
                        <td>{{ $row->reseller?->name ?? '—' }}</td>
                        <td>{{ $row->end_user_email }}</td>
                        <td>{{ $row->tool?->name ?? '—' }}</td>
                        <td>{{ $row->duration_months }}</td>
                        <td>₹{{ number_format((float) $row->amount_charged, 2) }}</td>
                        <td class="text-xs">
                            @if ($row->isCancelled())
                                Cancelled ({{ $row->cancelled_by_role }})
                            @elseif ($row->password_reset) Password reset
                            @elseif ($row->was_new_user) New
                            @else Existing @endif
                        </td>
                        <td class="text-sm">
                            @if ($row->isCancelled())
                                ₹{{ number_format((float) ($row->refund_amount ?? 0), 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-sm text-ink-secondary">
                            @if ($row->isCancelled())
                                Cancelled {{ $row->cancelled_at?->format('M j, Y H:i') }}
                            @else
                                {{ $row->created_at->format('M j, Y H:i') }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-ink-muted">No provisions.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-dash-pagination :paginator="$provisions" :per-page="$perPage" />
    </div>
@endsection
