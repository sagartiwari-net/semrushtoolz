@extends('layouts.dashboard')

@section('title', 'My Tools')

@section('content')
    <h1 class="dash-page-title">My Tools</h1>

    @php
        $activeCount = collect($tools)->where('active', true)->count();
        $lockedCount = collect($tools)->where('active', false)->count();
        $activeTools = collect($tools)->where('active', true)->values();
        $lockedTools = collect($tools)->where('active', false)->values();
    @endphp

    <div class="dash-stats-grid-3up">
        <x-dashboard.stat-card label="Total Tools" :value="count($tools)" icon="blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Active" :value="$activeCount" icon="green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Locked" :value="$lockedCount" icon="orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </x-dashboard.stat-card>
    </div>

    @if ($activeTools->isNotEmpty())
        <div class="dash-card mb-6">
            <h2 class="dash-card-title">Your active tools</h2>
            <div class="dash-tools-grid">
                @foreach ($activeTools as $tool)
                    <x-dashboard.tool-card :tool="$tool" />
                @endforeach
            </div>
        </div>
    @endif

    @if ($lockedTools->isNotEmpty())
        <div class="dash-card">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="dash-card-title mb-1">Subscribe to Unblock</h2>
                    <p class="text-sm text-ink-muted">Upgrade your plan to unlock more premium tools.</p>
                </div>
                <a href="{{ route('dashboard.shop') }}" class="ui-btn-outline text-sm">Browse plans</a>
            </div>
            <div class="dash-tools-grid">
                @foreach ($lockedTools as $tool)
                    <x-dashboard.tool-card :tool="$tool" />
                @endforeach
            </div>
        </div>
    @endif

    @if ($activeTools->isEmpty() && $lockedTools->isEmpty())
        <div class="dash-card">
            <p class="text-sm text-ink-muted">No tools available. <a href="{{ route('dashboard.shop') }}" class="text-accent hover:underline">Browse plans</a></p>
        </div>
    @endif
@endsection
