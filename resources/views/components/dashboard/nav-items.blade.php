@props(['user', 'active' => '', 'context' => 'desktop'])

@php
    $mainNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'grid'],
        ['label' => 'Shop', 'route' => 'dashboard.shop', 'icon' => 'store'],
        ['label' => 'My Tools', 'route' => 'dashboard.tools', 'icon' => 'tools'],
        ['label' => 'Orders', 'route' => 'dashboard.orders', 'icon' => 'receipt'],
    ];

    if (\App\Models\SiteSetting::walletConfig()['enabled']) {
        $mainNav[] = ['label' => 'Wallet', 'route' => 'dashboard.wallet', 'icon' => 'receipt'];
    }

    $accountNav = [
        ['label' => 'Extensions', 'route' => 'dashboard.extensions', 'icon' => 'puzzle'],
        ['label' => 'Affiliates', 'route' => 'dashboard.affiliates', 'icon' => 'users', 'badge' => 'New'],
        ['label' => 'Support', 'route' => 'dashboard.support', 'icon' => 'support'],
        ['label' => 'Profile', 'route' => 'dashboard.profile', 'icon' => 'profile'],
        ['label' => 'Settings', 'route' => 'dashboard.settings', 'icon' => 'settings'],
    ];

    $showMain = $context === 'desktop' || $context === 'mobile-drawer';
    $showAccount = $context === 'desktop' || $context === 'mobile-drawer';
@endphp

@if ($showMain)
    <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-ink-muted">Main Menu</p>
    @foreach ($mainNav as $item)
        <a href="{{ route($item['route']) }}" @class([
            'dash-nav-item',
            'dash-nav-item-active' => $active === $item['route'] || request()->routeIs($item['route']),
            'dash-nav-item-inactive' => $active !== $item['route'] && !request()->routeIs($item['route']),
        ]) @if($context === 'mobile-drawer') data-close-mobile-sidebar @endif>
            @include('components.dashboard.nav-icon', ['icon' => $item['icon']])
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
@endif

@if ($showAccount)
    <p class="mt-5 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-ink-muted">Account</p>
    @foreach ($accountNav as $item)
        <a href="{{ route($item['route']) }}" @class([
            'dash-nav-item',
            'dash-nav-item-active' => $active === $item['route'] || request()->routeIs($item['route']),
            'dash-nav-item-inactive' => $active !== $item['route'] && !request()->routeIs($item['route']),
        ]) @if($context === 'mobile-drawer') data-close-mobile-sidebar @endif>
            @include('components.dashboard.nav-icon', ['icon' => $item['icon']])
            <span>{{ $item['label'] }}</span>
            @if (!empty($item['badge']))
                <span class="ui-badge ml-auto bg-danger text-white">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endforeach
@endif

@if ($context === 'desktop' || $context === 'mobile-drawer')
    <p class="mt-5 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-ink-muted">General</p>
    <a href="{{ route('home') }}" @class(['dash-nav-item dash-nav-item-inactive']) @if($context === 'mobile-drawer') data-close-mobile-sidebar @endif>
        @include('components.dashboard.nav-icon', ['icon' => 'store'])
        <span>Back to Website</span>
    </a>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" @class(['dash-nav-item w-full text-left text-danger hover:bg-danger/5 hover:text-danger'])>
                @include('components.dashboard.nav-icon', ['icon' => 'logout'])
                <span>Log out</span>
            </button>
        </form>
@endif
