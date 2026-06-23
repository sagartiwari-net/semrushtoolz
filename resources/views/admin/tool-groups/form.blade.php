@extends('layouts.admin')

@section('title', $group->exists ? 'Edit Group' : 'Add Group')

@section('content')
    <form method="POST" action="{{ $group->exists ? route('admin.tool-groups.update', $group) : route('admin.tool-groups.store') }}" class="dash-card max-w-xl space-y-4">
        @csrf @if($group->exists) @method('PUT') @endif
        <div>
            <label class="ui-label">Linked Tool</label>
            <select class="ui-input" name="tool_id" required>
                @foreach ($tools as $tool)
                    <option value="{{ $tool->id }}" @selected(old('tool_id', $group->tool_id) == $tool->id)>{{ $tool->name }} ({{ $tool->slug }})</option>
                @endforeach
            </select>
        </div>
        <div><label class="ui-label">Group slug (hub URL)</label><input class="ui-input font-mono" name="slug" value="{{ old('slug', $group->slug) }}" placeholder="envato" required></div>
        <div><label class="ui-label">Hub title</label><input class="ui-input" name="title" value="{{ old('title', $group->title) }}" required></div>
        <div><label class="ui-label">Subtitle</label><input class="ui-input" name="subtitle" value="{{ old('subtitle', $group->subtitle) }}"></div>
        <div><label class="ui-label">Logo URL</label><input class="ui-input" name="logo_url" value="{{ old('logo_url', $group->logo_url) }}"></div>
        <div><label class="ui-label">Sort order</label><input class="ui-input" type="number" name="sort_order" value="{{ old('sort_order', $group->sort_order ?? 0) }}"></div>
        <label class="flex gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))> Active</label>
        <p class="text-xs text-ink-muted">After creating group, add access servers in <a href="{{ route('admin.tool-servers.index') }}" class="text-accent">Access Servers</a>.</p>
        <button class="ui-btn-primary">Save Group</button>
    </form>
@endsection
