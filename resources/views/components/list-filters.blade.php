{{--
  Shared GET filter toolbar.
  Props:
    - action: form action URL (default: current url)
    - search: placeholder string|false (false = hide search)
    - searchName: query key (default: q)
    - clearUrl: clear filters URL
    - filters: array of field defs:
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['all' => 'All', 'active' => 'Active'], 'value' => '...']
        ['name' => 'from', 'label' => 'From', 'type' => 'date', 'value' => '...']
        ['name' => 'tool_id', 'label' => 'Tool', 'type' => 'select', 'options' => ['' => 'All', 1 => 'Semrush'], 'value' => '']
--}}
@props([
    'action' => null,
    'search' => 'Search…',
    'searchName' => 'q',
    'searchValue' => null,
    'clearUrl' => null,
    'filters' => [],
    'preserve' => [],
])

@php
    $action = $action ?: url()->current();
    $clearUrl = $clearUrl ?: url()->current();
    $searchValue = $searchValue ?? request($searchName);
@endphp

<form method="GET" action="{{ $action }}" {{ $attributes->class('mb-5 space-y-3') }}>
    @foreach ($preserve as $key => $value)
        @if ($value !== null && $value !== '')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="flex flex-wrap items-end gap-2">
        @if ($search !== false)
            <div>
                <label class="ui-label text-xs">Search</label>
                <input class="ui-input max-w-xs py-1.5 text-sm" type="search" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $search }}" autocomplete="off">
            </div>
        @endif

        @foreach ($filters as $field)
            @php
                $type = $field['type'] ?? 'text';
                $name = $field['name'];
                $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $name));
                $value = $field['value'] ?? request($name);
            @endphp
            <div>
                <label class="ui-label text-xs">{{ $label }}</label>
                @if ($type === 'select')
                    <select class="ui-input w-auto max-w-[14rem] py-1.5 text-sm" name="{{ $name }}">
                        @foreach (($field['options'] ?? []) as $optValue => $optLabel)
                            <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'date')
                    <input class="ui-input w-auto py-1.5 text-sm" type="date" name="{{ $name }}" value="{{ $value }}">
                @elseif ($type === 'number')
                    <input class="ui-input w-28 py-1.5 text-sm" type="number" name="{{ $name }}" value="{{ $value }}" @if(isset($field['min'])) min="{{ $field['min'] }}" @endif step="{{ $field['step'] ?? '1' }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                @else
                    <input class="ui-input max-w-xs py-1.5 text-sm" type="text" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                @endif
            </div>
        @endforeach

        <div class="flex flex-wrap gap-2 pb-0.5">
            <button type="submit" class="ui-btn-primary text-sm">Apply</button>
            <a href="{{ $clearUrl }}" class="ui-btn-outline text-sm">Clear</a>
            {{ $slot }}
        </div>
    </div>
</form>
