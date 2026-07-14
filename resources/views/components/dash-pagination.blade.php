@props([
    'paginator',
    'perPage' => null,
])

@php
    $options = \App\Support\TablePageSize::OPTIONS;
    $perPage = $perPage ?? \App\Support\TablePageSize::DEFAULT;
    $query = request()->query();
    $except = array_merge(['per_page'], \App\Support\TablePageSize::PAGE_KEYS);
@endphp

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-4 sm:px-5') }}>
    <form method="GET" class="flex items-center gap-2 text-sm text-ink-secondary">
        @foreach (collect($query)->except($except) as $key => $value)
            @if (is_array($value))
                @foreach ($value as $k => $v)
                    <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="per_page_{{ $paginator->getPageName() }}">Rows</label>
        <select id="per_page_{{ $paginator->getPageName() }}" name="per_page" class="ui-input w-auto py-1.5 text-sm" onchange="this.form.submit()">
            @foreach ($options as $option)
                <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <span class="text-ink-muted">per page</span>
    </form>

    <div class="flex flex-wrap items-center gap-1 text-sm">
        @if ($paginator->total() > 0)
            <span class="px-2 text-ink-muted">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
        @else
            <span class="px-2 text-ink-muted">0 total</span>
        @endif

        @if ($paginator->onFirstPage())
            <span class="rounded-lg px-3 py-1.5 text-ink-muted">Previous</span>
        @else
            <a class="rounded-lg border border-line px-3 py-1.5 hover:border-accent" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="rounded-lg border border-line px-3 py-1.5 hover:border-accent" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @else
            <span class="rounded-lg px-3 py-1.5 text-ink-muted">Next</span>
        @endif
    </div>
</div>
