@props(['active' => ''])

@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.index', 'icon' => 'grid', 'active' => 'admin.index'],
        ['label' => 'Users', 'route' => 'admin.users', 'icon' => 'users', 'active' => 'admin.users'],
        ['label' => 'Unverified Users', 'route' => 'admin.users.unverified', 'icon' => 'users', 'active' => 'admin.users.unverified'],
        ['label' => 'Plans', 'route' => 'admin.plans.index', 'icon' => 'package', 'active' => 'admin.plans.*'],
        ['label' => 'Tools', 'route' => 'admin.tools.index', 'icon' => 'server', 'active' => 'admin.tools.*'],
        ['label' => 'Tool Groups', 'route' => 'admin.tool-groups.index', 'icon' => 'grid', 'active' => 'admin.tool-groups.*'],
        ['label' => 'Tool Pages', 'route' => 'admin.articles.index', 'icon' => 'ticket', 'active' => 'admin.articles.*'],
        ['label' => 'Homepage', 'route' => 'admin.homepage.edit', 'icon' => 'store', 'active' => 'admin.homepage.*'],
        ['label' => 'SEO & Sitemap', 'route' => 'admin.seo.edit', 'icon' => 'chart', 'active' => 'admin.seo.*'],
        ['label' => 'Legal Pages', 'route' => 'admin.legal-pages.index', 'icon' => 'ticket', 'active' => 'admin.legal-pages.*'],
        ['label' => 'Orders', 'route' => 'admin.orders', 'icon' => 'receipt', 'active' => 'admin.orders*'],
        ['label' => 'Payments', 'route' => 'admin.payments', 'icon' => 'credit', 'active' => 'admin.payments'],
        ['label' => 'Wallet', 'route' => 'admin.wallet', 'icon' => 'credit', 'active' => 'admin.wallet*'],
        ['label' => 'Payment Integration', 'route' => 'admin.payment-integration.edit', 'icon' => 'credit', 'active' => 'admin.payment-integration.*'],
        ['label' => 'Access Servers', 'route' => 'admin.tool-servers.index', 'icon' => 'server', 'active' => 'admin.tool-servers.*'],
        ['label' => 'Extension', 'route' => 'admin.extension-settings.edit', 'icon' => 'settings', 'active' => 'admin.extension-settings.*'],
        ['label' => 'Email Setup', 'route' => 'admin.email-settings.edit', 'icon' => 'mail', 'active' => 'admin.email-settings.*'],
        ['label' => 'Email Presets', 'route' => 'admin.email-presets.index', 'icon' => 'mail', 'active' => 'admin.email-presets.*'],
        ['label' => 'Sessions', 'route' => 'admin.sessions', 'icon' => 'chart', 'active' => 'admin.sessions'],
        ['label' => 'Tickets', 'route' => 'admin.tickets', 'icon' => 'ticket', 'active' => 'admin.tickets'],
        ['label' => 'Security', 'route' => 'admin.security', 'icon' => 'shield', 'active' => 'admin.security*'],
        ['label' => 'Affiliates', 'route' => 'admin.affiliates', 'icon' => 'users', 'active' => 'admin.affiliates'],
        ['label' => 'Coupons', 'route' => 'admin.coupons.index', 'icon' => 'coupon', 'active' => 'admin.coupons.*'],
        ['label' => 'Settings', 'route' => 'admin.settings', 'icon' => 'settings', 'active' => 'admin.settings'],
        ['label' => 'My Profile', 'route' => 'admin.profile', 'icon' => 'profile', 'active' => 'admin.profile'],
    ];
    $authUser = auth()->user();
    $adminInitials = collect(explode(' ', $authUser?->name ?? 'Admin'))
        ->filter()
        ->take(2)
        ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
        ->implode('') ?: 'A';
    $adminAvatar = $authUser ? [
        'name' => $authUser->name,
        'initials' => $adminInitials,
        'avatar_url' => $authUser->avatarUrl(),
    ] : ['name' => 'Admin', 'initials' => 'A', 'avatar_url' => null];
@endphp

<aside class="island-sidebar sticky top-4 hidden h-[calc(100vh-32px)] lg:flex">
    <div class="border-b border-line px-4 py-5">
        <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full bg-ink py-1.5 pl-2 pr-3.5">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <span class="text-sm font-bold text-white">Admin</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-ink-muted">Management</p>
        @foreach ($nav as $item)
            @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
            <a href="{{ route($item['route']) }}" @class([
                'dash-nav-item',
                'dash-nav-item-active' => $isActive,
                'dash-nav-item-inactive' => !$isActive,
            ])>
                @include('components.dashboard.nav-icon', ['icon' => $item['icon']])
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <p class="mt-5 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-ink-muted">Site</p>
        <a href="{{ route('home') }}" class="dash-nav-item dash-nav-item-inactive" target="_blank">
            @include('components.dashboard.nav-icon', ['icon' => 'store'])
            <span>View Website</span>
        </a>
        <a href="{{ route('dashboard.index') }}" class="dash-nav-item dash-nav-item-inactive">
            @include('components.dashboard.nav-icon', ['icon' => 'grid'])
            <span>User Dashboard</span>
        </a>
    </nav>

    <div class="border-t border-line p-3">
        <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 rounded-xl bg-surface p-3 transition hover:bg-surface/80">
            <x-dashboard.user-avatar :user="$adminAvatar" size="sm" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold">{{ $authUser?->name ?? 'Admin' }}</div>
                <div class="truncate text-xs text-ink-muted">{{ $authUser?->email ?? '' }}</div>
            </div>
        </a>
    </div>
</aside>
