@props(['filters', 'exportCsvRoute', 'exportExcelRoute'])

@php
    $query = $filters->queryParams();
@endphp

<form method="GET" class="mb-4 space-y-3 rounded-xl border border-line bg-surface-2 p-4">
  @foreach (collect($query)->except(['report_from', 'report_to', 'report_view', 'hide_empty', 'report_month', 'page']) as $key => $value)
    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
  @endforeach

  <div class="flex flex-wrap items-end gap-3">
    <div>
      <label class="mb-1 block text-xs font-medium text-ink-muted">View</label>
      <select name="report_view" class="ui-input w-auto py-1.5 text-sm">
        <option value="monthly" @selected($filters->view === 'monthly')>Monthly</option>
        <option value="daily" @selected($filters->view === 'daily')>Daily</option>
      </select>
    </div>
    <div>
      <label class="mb-1 block text-xs font-medium text-ink-muted">From</label>
      <input type="date" name="report_from" value="{{ $filters->from->toDateString() }}" class="ui-input w-auto py-1.5 text-sm">
    </div>
    <div>
      <label class="mb-1 block text-xs font-medium text-ink-muted">To</label>
      <input type="date" name="report_to" value="{{ $filters->to->toDateString() }}" class="ui-input w-auto py-1.5 text-sm">
    </div>
    <label class="flex items-center gap-2 pb-1.5 text-sm text-ink-secondary">
      <input type="checkbox" name="hide_empty" value="1" @checked($filters->hideEmpty) class="rounded border-line">
      Hide empty days
    </label>
    <button type="submit" class="ui-btn-primary text-xs">Apply</button>
  </div>

  @if ($filters->view === 'daily' && $filters->month)
    <div class="flex flex-wrap items-center gap-2 text-xs text-ink-muted">
      <span>Showing daily data for <strong class="text-ink">{{ $filters->periodLabel() }}</strong></span>
      <a href="{{ request()->url() }}?{{ http_build_query(collect($query)->except('report_month')->put('report_view', 'monthly')->filter()->all()) }}" class="text-accent hover:underline">Back to monthly view</a>
    </div>
  @endif
</form>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
  <p class="text-xs text-ink-muted">{{ $filters->periodLabel() }} · {{ $filters->view === 'monthly' ? 'Click a month to see daily breakdown' : 'Daily breakdown' }}</p>
  <div class="flex gap-2">
    <a href="{{ $exportCsvRoute }}?{{ http_build_query($query) }}" class="ui-btn-outline text-xs">Export CSV</a>
    <a href="{{ $exportExcelRoute }}?{{ http_build_query($query) }}" class="ui-btn-outline text-xs">Export Excel</a>
  </div>
</div>
