@props(['paginator', 'perPage' => 20])

@php
    $options = \App\Support\TablePageSize::OPTIONS;
    $query = request()->query();
@endphp

<div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
    <form method="GET" class="flex items-center gap-2 text-sm text-ink-secondary">
        @foreach (collect($query)->except(['per_page', 'page']) as $key => $value)
            @if (is_array($value))
                @foreach ($value as $k => $v)
                    <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="per_page">Rows per page</label>
        <select id="per_page" name="per_page" class="ui-input w-auto py-1.5 text-sm" onchange="this.form.submit()">
            @foreach ($options as $option)
                <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </form>

    @if ($paginator->hasPages())
        <div class="flex flex-wrap items-center gap-1 text-sm">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg px-3 py-1.5 text-ink-muted">Previous</span>
            @else
                <a class="rounded-lg border border-line px-3 py-1.5 hover:border-accent" href="{{ $paginator->previousPageUrl() }}">Previous</a>
            @endif

            <span class="px-2 text-ink-muted">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>

            @if ($paginator->hasMorePages())
                <a class="rounded-lg border border-line px-3 py-1.5 hover:border-accent" href="{{ $paginator->nextPageUrl() }}">Next</a>
            @else
                <span class="rounded-lg px-3 py-1.5 text-ink-muted">Next</span>
            @endif
        </div>
    @else
        <span class="text-sm text-ink-muted">{{ $paginator->total() }} total</span>
    @endif
</div>
