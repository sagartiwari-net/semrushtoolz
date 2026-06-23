@props(['user', 'active' => ''])

@php
    $mainNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'grid'],
        ['label' => 'Shop', 'route' => 'dashboard.shop', 'icon' => 'store'],
        ['label' => 'My Tools', 'route' => 'dashboard.tools', 'icon' => 'tools'],
        ['label' => 'Orders', 'route' => 'dashboard.orders', 'icon' => 'receipt'],
    ];
@endphp

{{-- Desktop sidebar --}}
<aside class="island-sidebar sticky top-4 hidden h-[calc(100vh-32px)] lg:flex">
    <div class="border-b border-line px-4 py-5">
        <x-logo href="{{ route('home') }}" />
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <x-dashboard.nav-items :user="$user" :active="$active" context="desktop" />
    </nav>

    <div class="border-t border-line p-3">
        <a href="{{ route('dashboard.profile') }}" class="flex items-center gap-3 rounded-xl bg-surface p-3 transition hover:bg-surface/80">
            <x-dashboard.user-avatar :user="$user" size="sm" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold">{{ $user['name'] }}</div>
                <div class="text-xs text-ink-muted">{{ $user['role_label'] }}</div>
            </div>
        </a>
    </div>
</aside>

{{-- Mobile drawer sidebar --}}
<div id="mobile-sidebar" class="pointer-events-none fixed inset-0 z-50 lg:hidden" aria-hidden="true">
    <div id="mobile-sidebar-backdrop" class="absolute inset-0 bg-ink/50 opacity-0 transition-opacity duration-300"></div>
    <aside id="mobile-sidebar-panel" class="absolute left-0 top-0 flex h-full w-[min(300px,85vw)] -translate-x-full flex-col border-r border-line bg-white shadow-xl transition-transform duration-300">
        <div class="flex items-center justify-between border-b border-line px-4 py-4">
            <x-logo href="{{ route('home') }}" />
            <button type="button" id="mobile-sidebar-close" class="btn btn-ghost btn-sm btn-circle" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            <x-dashboard.nav-items :user="$user" :active="$active" context="mobile-drawer" />
        </nav>

        <div class="border-t border-line p-3">
            <a href="{{ route('dashboard.profile') }}" class="flex items-center gap-3 rounded-xl bg-surface p-3">
                <x-dashboard.user-avatar :user="$user" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold">{{ $user['name'] }}</div>
                    <div class="text-xs text-ink-muted">{{ $user['role_label'] }}</div>
                </div>
            </a>
        </div>
    </aside>
</div>

{{-- Mobile bottom nav: important links only --}}
<nav class="fixed inset-x-0 bottom-0 z-40 flex border-t border-line bg-white px-1 py-1.5 lg:hidden">
    @foreach ($mainNav as $item)
        <a href="{{ route($item['route']) }}" @class([
            'flex flex-1 flex-col items-center gap-0.5 rounded-lg px-1 py-1.5 text-[10px] font-medium',
            'text-accent' => request()->routeIs($item['route']),
            'text-ink-muted' => !request()->routeIs($item['route']),
        ])>
            @include('components.dashboard.nav-icon', ['icon' => $item['icon'], 'size' => 16])
            {{ $item['label'] }}
        </a>
    @endforeach
    <button type="button" id="mobile-sidebar-open" @class([
        'flex flex-1 flex-col items-center gap-0.5 rounded-lg px-1 py-1.5 text-[10px] font-medium text-ink-muted',
    ]) aria-label="Open menu">
        @include('components.dashboard.nav-icon', ['icon' => 'menu', 'size' => 16])
        More
    </button>
</nav>
