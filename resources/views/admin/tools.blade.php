@extends('layouts.admin')

@section('title', 'Tools & Cloud')

@section('content')
    <div class="dash-tools-grid">
        @foreach ($tools as $tool)
            <div class="dash-tool-card">
                <div class="flex items-center gap-3">
                    <img src="{{ $tool['logo'] }}" alt="" class="h-10 w-10 object-contain">
                    <div>
                        <div class="font-bold text-ink">{{ $tool['name'] }}</div>
                        <span class="dash-badge-online">Online</span>
                    </div>
                </div>
                @if ($tool['seats'])
                    <div class="text-sm text-ink-secondary">Seats in use: <strong>{{ $tool['seats'] }}</strong></div>
                @endif
                <div class="flex gap-2">
                    <button class="ui-btn-outline flex-1 text-xs">Maintenance</button>
                    <button class="ui-btn-ghost flex-1 text-xs text-danger">Force Kill All</button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="dash-card mt-4">
        <h3 class="dash-card-title">Go Proxy Health</h3>
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <span class="dash-badge-online">API Reachable</span>
            <span class="text-ink-muted">Last ping: 2s ago</span>
            <span class="text-ink-muted">Response: 45ms</span>
        </div>
    </div>
@endsection
