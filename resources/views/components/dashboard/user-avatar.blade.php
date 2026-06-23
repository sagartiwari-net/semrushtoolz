@props([
    'user',
    'size' => 'md',
    'class' => '',
])

@php
    $sizes = [
        'xs' => 'h-8 w-8 text-xs rounded-full',
        'sm' => 'h-9 w-9 text-xs rounded-lg',
        'md' => 'h-20 w-20 text-2xl rounded-2xl',
        'lg' => 'h-24 w-24 text-3xl rounded-2xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if (! empty($user['avatar_url']))
    <img
        src="{{ $user['avatar_url'] }}"
        alt="{{ $user['name'] ?? 'Profile' }}"
        @class(['shrink-0 object-cover', $sizeClass, $class])
    >
@else
    <div @class([
        'flex shrink-0 items-center justify-center bg-gradient-to-br from-accent to-accent-light font-bold text-white',
        $sizeClass,
        $class,
    ])>
        {{ $user['initials'] ?? 'U' }}
    </div>
@endif
