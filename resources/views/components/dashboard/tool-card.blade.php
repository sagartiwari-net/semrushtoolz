@props(['tool'])

@php
    $hubSlug = app(\App\Services\ToolAccessService::class)->hubSlugForTool($tool['id']);
    $accessType = $tool['access_type'] ?? 'cloud';
@endphp

<div @class([
    'dash-tool-card',
    'dash-tool-card-featured' => $tool['featured'] ?? false,
    'opacity-75' => !($tool['active'] ?? false),
])>
    <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-surface">
            <img src="{{ $tool['logo'] }}" alt="{{ $tool['name'] }}" class="h-8 w-8 object-contain">
        </div>
        <div class="min-w-0 flex-1">
            <div class="font-bold text-ink">{{ $tool['name'] }}</div>
            <div class="text-xs text-ink-muted">{{ $tool['desc'] }}</div>
        </div>
        @if ($tool['active'])
            <span class="dash-badge-online">
                <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                Active
            </span>
        @else
            <span class="dash-badge-offline">Locked</span>
        @endif
    </div>

    @if ($tool['last_accessed'] ?? null)
        <div class="text-xs text-ink-muted">Last accessed: {{ $tool['last_accessed'] }}</div>
    @endif

    @if ($tool['active'])
        @if ($hubSlug)
            <a href="{{ route('dashboard.tools.hub', $hubSlug) }}" class="{{ ($tool['featured'] ?? false) ? 'ui-btn-primary' : 'ui-btn-outline' }} w-full justify-center">
                @if ($accessType === 'whatsapp')
                    Activate via WhatsApp
                @elseif ($accessType === 'credentials')
                    View Credentials
                @elseif ($accessType === 'extension')
                    Open Access Panel
                @else
                    Open Access Panel
                @endif
            </a>
        @elseif ($accessType === 'extension')
            <a href="{{ route('dashboard.extensions') }}" class="ui-btn-outline w-full justify-center">Get Extension</a>
        @endif
    @else
        <a href="{{ route('dashboard.shop') }}" class="ui-btn-outline w-full justify-center">Subscribe to Unlock</a>
    @endif
</div>
