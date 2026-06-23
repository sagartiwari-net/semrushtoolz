@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <h1 class="dash-page-title">Welcome back, {{ explode(' ', $user['name'])[0] }} 👋</h1>

    @if ($user['days_remaining'] > 0 && $user['days_remaining'] <= 7)
        <div class="mb-4 rounded-2xl border border-warning/30 bg-warning/10 px-5 py-4 text-sm text-ink-secondary">
            <strong class="text-ink">Plan expiring soon!</strong> Your subscription expires in {{ $user['days_remaining'] }} days.
            <a href="{{ route('dashboard.shop') }}" class="ml-1 font-medium text-accent hover:underline">Renew now</a>
        </div>
    @endif

    <div class="dash-stats-grid">
        @if ($walletEnabled ?? false)
            <x-dashboard.stat-card label="Wallet Balance" :value="$walletBalanceLabel" sub="Use for INR subscriptions" icon="green">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            </x-dashboard.stat-card>
        @endif
        <x-dashboard.stat-card label="Active Plan" :value="$user['plan']" :sub="$user['plan_status']" icon="orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Expiry" :value="$user['expires_at']" :sub="$user['days_remaining'] > 0 ? $user['days_remaining'].' days remaining' : 'No active plan'" icon="green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Status" :value="$user['plan_status']" :sub="$user['days_remaining'] > 0 ? 'Subscription is current' : 'Subscribe to access tools'" icon="blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Top Tool" :value="$topTool['name'] ?? '—'" :sub="isset($topTool['count']) ? 'Accessed '.$topTool['count'].' times' : 'No tool usage yet'" icon="purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        </x-dashboard.stat-card>
    </div>

    <div class="dash-card">
        <h2 class="dash-card-title">Quick Access</h2>
        <div class="dash-tools-grid">
            @forelse ($tools as $tool)
                @if ($tool['active'])
                    <x-dashboard.tool-card :tool="$tool" />
                @endif
            @empty
                <p class="text-sm text-ink-muted">No tools available. <a href="{{ route('dashboard.shop') }}" class="text-accent hover:underline">Browse plans</a></p>
            @endforelse
        </div>
    </div>

    <div class="dash-card">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="dash-card-title mb-0">Recent Activity</h2>
            @if (($activityTotal ?? 0) > count($activity))
                <a href="{{ route('dashboard.activity') }}" class="text-sm font-medium text-accent hover:underline">View all</a>
            @endif
        </div>
        @if (count($activity) > 0)
            <ul class="space-y-4">
                @foreach ($activity as $item)
                    <li class="flex gap-3">
                        <span @class([
                            'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                            'bg-accent' => $item['color'] === 'accent',
                            'bg-success' => $item['color'] === 'success',
                            'bg-blue' => $item['color'] === 'blue',
                        ])></span>
                        <div>
                            <div class="text-sm font-semibold text-ink">{{ $item['title'] }}</div>
                            <div class="text-xs text-ink-muted">{{ $item['meta'] }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-ink-muted">No recent activity yet. Access a tool or complete a purchase to see updates here.</p>
        @endif
    </div>
@endsection
