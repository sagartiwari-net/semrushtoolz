@extends('layouts.admin')

@section('title', $page->exists ? 'Edit Legal Page' : 'New Legal Page')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.legal-pages.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Legal Pages</a>
        <h1 class="dash-page-title mt-2">{{ $page->exists ? 'Edit Page' : 'New Legal Page' }}</h1>
        @if ($page->exists)
            <a href="{{ route('admin.legal-pages.preview', $page) }}" target="_blank" class="mt-2 inline-flex ui-btn-ghost text-sm">Preview page</a>
        @endif
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $page->exists ? route('admin.legal-pages.update', $page) : route('admin.legal-pages.store') }}" class="dash-card max-w-4xl space-y-6">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ui-label">Page title *</label>
                <input class="ui-input" name="title" value="{{ old('title', $page->title) }}" required>
            </div>
            <div>
                <label class="ui-label">Slug *</label>
                <input class="ui-input font-mono text-sm" name="slug" id="legal-slug" value="{{ old('slug', $page->slug) }}" required @readonly($page->exists && in_array($page->slug, ['terms', 'privacy', 'refund']))>
                <p class="mt-1 text-xs text-ink-muted">Core slugs: terms, privacy, refund. Others publish at /legal/your-slug</p>
            </div>
        </div>

        <div>
            <label class="ui-label">Page content (HTML) *</label>
            <textarea class="ui-input min-h-[320px] font-mono text-xs" name="html_body" rows="16" required>{{ old('html_body', $page->html_body) }}</textarea>
        </div>

        <div class="border-t border-line pt-4 space-y-4">
            <h2 class="font-semibold text-ink">SEO</h2>
            <div>
                <label class="ui-label">SEO title *</label>
                <input class="ui-input" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" required>
            </div>
            <div>
                <label class="ui-label">SEO description *</label>
                <textarea class="ui-input" name="seo_description" rows="2" required>{{ old('seo_description', $page->seo_description) }}</textarea>
            </div>
            <div>
                <label class="ui-label">SEO keywords</label>
                <input class="ui-input" name="seo_keywords" value="{{ old('seo_keywords', $page->seo_keywords) }}" placeholder="keyword one, keyword two">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_published" value="1" class="rounded" @checked(old('is_published', $page->is_published ?? true))>
                Published
            </label>
            <div>
                <label class="ui-label">Sort order</label>
                <input class="ui-input w-24" type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" min="0">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="ui-btn-primary">Save Page</button>
            <a href="{{ route('admin.legal-pages.index') }}" class="ui-btn-outline">Cancel</a>
        </div>
    </form>
@endsection
