@props([
    'label',
    'value',
    'sub' => null,
    'icon' => 'default',
    'valueClass' => '',
])

@php
    $iconClass = match ($icon) {
        'green' => 'admin-stat-icon-green',
        'blue' => 'admin-stat-icon-blue',
        'purple' => 'admin-stat-icon-purple',
        default => 'admin-stat-icon-orange',
    };
@endphp

<div {{ $attributes->merge(['class' => 'dash-stat-card']) }}>
    <div class="mb-3.5 flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ $label }}</span>
        <div class="{{ $iconClass }}">
            {{ $slot }}
        </div>
    </div>
    <div class="text-2xl font-extrabold tracking-tight text-ink {{ $valueClass }}">{{ $value }}</div>
    @if ($sub)
        <div class="mt-1 text-xs text-ink-muted">{{ $sub }}</div>
    @endif
</div>
