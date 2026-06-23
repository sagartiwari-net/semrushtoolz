@extends('layouts.public')

@section('title', $seo['title'])

@section('seo_meta')
    <x-seo-meta
        :title="$seo['title']"
        :description="$seo['description']"
        :keywords="$seo['keywords'] ?? ''"
        :canonical="$page->publicUrl()"
    />
    @if (!empty($isPreview))
        <meta name="robots" content="noindex, nofollow">
    @endif
@endsection

@section('content')
    <x-preview-banner :is-preview="!empty($isPreview)" :back-url="$backUrl ?? null" />

    <section class="border-b border-line bg-surface py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <nav class="mb-4 text-sm text-ink-muted">
                <a href="{{ url('/') }}" class="hover:text-accent">Home</a>
                <span class="mx-2">/</span>
                <span>{{ $page->title }}</span>
            </nav>
            <h1 class="text-3xl font-bold tracking-tight text-ink">{{ $page->title }}</h1>
            <p class="mt-2 text-sm text-ink-muted">Last updated: {{ $page->updated_at->format('F j, Y') }}</p>
        </div>
    </section>

    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 prose prose-sm max-w-none text-ink-secondary">
        {!! $page->html_body !!}
    </article>
@endsection
