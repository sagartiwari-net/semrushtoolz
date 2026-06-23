@extends('layouts.dashboard')

@section('title', 'Activity')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Dashboard</a>
        <h1 class="dash-page-title mt-2">Activity</h1>
        <p class="text-sm text-ink-secondary">Sign-ins, tool access, and purchases on your account.</p>
    </div>

    <div class="dash-card">
        @if ($activity->count() > 0)
            <ul class="space-y-4">
                @foreach ($activity as $item)
                    <li class="flex gap-3 border-b border-line pb-4 last:border-0 last:pb-0">
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

            <x-dash-pagination :paginator="$activity" :per-page="$perPage" />
        @else
            <p class="text-sm text-ink-muted">No activity recorded yet.</p>
        @endif
    </div>
@endsection
