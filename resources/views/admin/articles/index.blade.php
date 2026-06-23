@extends('layouts.admin')

@section('title', 'Tool Product Pages')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-secondary">SEO product/purchase pages for each tool (e.g. <code class="text-xs">/tools/semrush-group-buy</code>). Linked tools get named routes for internal linking.</p>
        <a href="{{ route('admin.articles.create') }}" class="ui-btn-primary">+ Add Tool Page</a>
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>URL</th>
                    <th>Tool</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr>
                        <td class="font-medium text-ink">{{ $article->title }}</td>
                        <td>
                            @if ($article->is_published)
                                <a href="{{ url('/'.$article->url_path) }}" target="_blank" class="text-xs text-accent hover:underline">/{{ $article->url_path }}</a>
                            @else
                                <span class="text-xs text-ink-muted">/{{ $article->url_path }}</span>
                            @endif
                        </td>
                        <td>{{ $article->tool?->name ?? '—' }}</td>
                        <td>
                            <span @class(['dash-badge-online' => $article->is_published, 'dash-badge-offline' => !$article->is_published])>
                                {{ $article->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" class="ui-btn-ghost text-xs">Preview</a>
                            <a href="{{ route('admin.articles.edit', $article) }}" class="ui-btn-ghost text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.articles.toggle', $article) }}" class="inline">@csrf
                                <button class="ui-btn-ghost text-xs">{{ $article->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="inline" onsubmit="return confirm('Delete this article?')">@csrf @method('DELETE')
                                <button class="ui-btn-ghost text-xs text-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-ink-muted">No articles yet. Create your first group-buy page.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
