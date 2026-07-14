@props(['pendingRequests' => null])

@php
    $pending = $pendingRequests ?? \App\Models\ResellerBalanceRequest::query()
        ->where('status', \App\Models\ResellerBalanceRequest::STATUS_PENDING)
        ->count();

    $active = match (true) {
        request()->routeIs('admin.resellers.pricing.*') => 'pricing',
        request()->routeIs('admin.resellers.requests*') => 'requests',
        request()->routeIs('admin.resellers.reports*') => 'reports',
        request()->routeIs('admin.resellers.create', 'admin.resellers.store', 'admin.resellers.edit', 'admin.resellers.update', 'admin.resellers.show') => 'resellers',
        default => 'resellers',
    };

    $tabs = [
        'resellers' => [
            'label' => 'Resellers',
            'url' => route('admin.resellers.index'),
        ],
        'pricing' => [
            'label' => 'Default pricing',
            'url' => route('admin.resellers.pricing.edit'),
        ],
        'requests' => [
            'label' => 'Balance requests'.($pending > 0 ? " ({$pending})" : ''),
            'url' => route('admin.resellers.requests'),
        ],
        'reports' => [
            'label' => 'Reports',
            'url' => route('admin.resellers.reports'),
        ],
    ];
@endphp

<div class="dash-tabs mb-5">
    <div class="dash-tab-list" role="tablist">
        @foreach ($tabs as $key => $tab)
            <a
                href="{{ $tab['url'] }}"
                role="tab"
                @class(['dash-tab', 'dash-tab-active' => $active === $key])
                aria-selected="{{ $active === $key ? 'true' : 'false' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>
