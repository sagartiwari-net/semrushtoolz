@props(['report', 'breakdown', 'perPage', 'drillDownBaseUrl' => null])

@php
    $isMonthly = $report['breakdown_type'] === 'monthly';
    $title = $isMonthly ? 'Monthly breakdown' : 'Daily breakdown';
@endphp

<h4 class="mb-3 text-sm font-bold text-ink">{{ $title }}</h4>
<div class="dash-table-wrap mb-6 border-0 shadow-none">
    <table class="dash-table">
        <thead>
            <tr>
                <th>{{ $isMonthly ? 'Month' : 'Date' }}</th>
                <th>Clicks</th>
                <th>Signups</th>
                <th>Orders</th>
                @if (! $isMonthly)
                    <th>Trend</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($breakdown as $row)
                @php $max = max(1, $row['clicks'], $row['signups'], $row['orders']); @endphp
                <tr>
                    <td>
                        @if ($isMonthly && $drillDownBaseUrl && ! empty($row['month_key']))
                            <a href="{{ $drillDownBaseUrl }}?{{ http_build_query(array_merge(request()->except('page'), ['tab' => 'reports', 'report_view' => 'daily', 'report_month' => $row['month_key']])) }}" class="font-medium text-accent hover:underline">{{ $row['date'] }}</a>
                        @else
                            {{ $row['date'] }}
                        @endif
                    </td>
                    <td>{{ $row['clicks'] }}</td>
                    <td>{{ $row['signups'] }}</td>
                    <td>{{ $row['orders'] }}</td>
                    @if (! $isMonthly)
                        <td class="w-32">
                            <div class="dash-mini-bar"><div class="dash-mini-bar-fill" style="width: {{ round(($row['clicks'] / $max) * 100) }}%"></div></div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isMonthly ? 4 : 5 }}" class="py-6 text-center text-ink-muted">No activity in this period{{ request()->boolean('hide_empty') ? ' (empty rows hidden)' : '' }}.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <x-dash-pagination :paginator="$breakdown" :per-page="$perPage" />
</div>
