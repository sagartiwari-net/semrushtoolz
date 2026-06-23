@props([
    'tabs' => [],
    'active' => '',
    'preserve' => [],
])

@php
    $active = $active ?: array_key_first($tabs);
@endphp

<div class="dash-tabs">
    <div class="dash-tab-list" role="tablist">
        @foreach ($tabs as $key => $label)
            @php
                $params = array_merge(request()->only($preserve), ['tab' => $key]);
                $isActive = $active === $key;
            @endphp
            <a
                href="{{ url()->current() . '?' . http_build_query($params) }}"
                role="tab"
                @class(['dash-tab', 'dash-tab-active' => $isActive])
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="dash-tab-panels">
        {{ $slot }}
    </div>
</div>
