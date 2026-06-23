@extends('layouts.admin')

@section('title', 'Legal Pages')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-secondary">Terms, Privacy, Refund and any additional policy pages. Core pages use short URLs (/terms, /privacy, /refund).</p>
        <a href="{{ route('admin.legal-pages.create') }}" class="ui-btn-primary">+ Add Page</a>
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>URL</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td class="font-medium text-ink">{{ $page->title }}</td>
                        <td>
                            <a href="{{ $page->publicUrl() }}" target="_blank" class="text-xs text-accent hover:underline">{{ $page->publicPath() }}</a>
                        </td>
                        <td>
                            <span @class(['dash-badge-online' => $page->is_published, 'dash-badge-offline' => ! $page->is_published])>
                                {{ $page->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.legal-pages.preview', $page) }}" target="_blank" class="ui-btn-ghost text-xs">Preview</a>
                            <a href="{{ route('admin.legal-pages.edit', $page) }}" class="ui-btn-ghost text-xs">Edit</a>
                            @unless (in_array($page->slug, ['terms', 'privacy', 'refund']))
                                <form method="POST" action="{{ route('admin.legal-pages.destroy', $page) }}" class="inline" onsubmit="return confirm('Delete this page?')">
                                    @csrf @method('DELETE')
                                    <button class="ui-btn-ghost text-xs text-danger">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-sm text-ink-muted">No legal pages yet. Run the seeder or create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
