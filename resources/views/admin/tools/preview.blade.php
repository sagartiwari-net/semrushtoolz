@extends('layouts.admin')

@section('title', 'Preview: '.$tool->name)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.tools.edit', $tool) }}" class="text-sm text-ink-muted hover:text-accent">&larr; Edit Tool</a>
            <h1 class="dash-page-title mt-2">Preview: {{ $tool->name }}</h1>
            <p class="text-sm text-ink-secondary">No tool product page linked yet. This is how the tool appears in the shop.</p>
        </div>
        <a href="{{ route('admin.articles.create') }}?tool_id={{ $tool->id }}" class="ui-btn-primary">Create Tool Page</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="dash-card">
            <h2 class="mb-4 font-semibold text-ink">Shop card</h2>
            <div class="max-w-xs">
                <x-shop-tool-card :tool="$shop" :index="0" />
            </div>
        </div>

        <div class="dash-card space-y-4">
            <h2 class="font-semibold text-ink">SEO preview</h2>
            <div class="rounded-lg border border-line bg-canvas p-4 text-sm">
                <p class="text-accent">{{ $seo['title'] }}</p>
                <p class="text-success text-xs">{{ url('/tools/'.str($tool->slug)->slug().'-group-buy') }}</p>
                <p class="mt-1 text-ink-secondary">{{ $seo['description'] }}</p>
            </div>
            @if ($tool->thumbnailUrl())
                <div>
                    <p class="ui-label">Thumbnail</p>
                    <img src="{{ $tool->thumbnailUrl() }}" alt="" class="mt-1 h-16 w-auto rounded border border-line bg-white p-1">
                </div>
            @endif
            <div>
                <p class="ui-label">Keywords</p>
                <p class="text-sm text-ink-secondary">{{ $seo['keywords'] ?: '—' }}</p>
            </div>
        </div>
    </div>
@endsection
