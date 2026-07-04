@extends('layouts.admin')

@section('title', $group->exists ? 'Edit Group' : 'Add Group')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.tool-groups.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Tool Groups</a>
        <h1 class="dash-page-title mt-2">{{ $group->exists ? 'Edit Group' : 'Add Group' }}</h1>
        <p class="mt-1 text-sm text-ink-muted">A group is the hub page (e.g. Bonus Tools). Access Servers are the individual buttons on that hub.</p>
    </div>

    <div class="mb-5 max-w-xl rounded-xl border border-line bg-surface/60 px-4 py-3 text-sm text-ink-secondary">
        <p class="font-medium text-ink">How this works</p>
        <ol class="mt-2 list-decimal space-y-1 pl-4 text-xs text-ink-muted">
            <li><strong>Tools</strong> — catalog item users subscribe to (must be On).</li>
            <li><strong>Tool Group</strong> — hub page linked to that tool (this form).</li>
            <li><strong>Access Servers</strong> — add 8–10 buttons under this group.</li>
        </ol>
    </div>

    <form method="POST" action="{{ $group->exists ? route('admin.tool-groups.update', $group) : route('admin.tool-groups.store') }}" class="dash-card max-w-xl space-y-4">
        @csrf @if($group->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="ui-label">Linked Tool (subscription grant)</label>
            <select class="ui-input" name="tool_id" required>
                <option value="">— Select tool —</option>
                @foreach ($tools as $tool)
                    <option value="{{ $tool->id }}" @selected(old('tool_id', $group->tool_id) == $tool->id)>
                        {{ $tool->name }} ({{ $tool->slug }}){{ $tool->is_active ? '' : ' — Off' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-muted">
                Users who have this tool in their plan can open the hub.
                Tool missing or Off? Turn it On in <a href="{{ route('admin.tools.index') }}" class="text-accent hover:underline">Tools</a> first.
            </p>
        </div>
        <div>
            <label class="ui-label">Group slug (hub URL)</label>
            <input class="ui-input font-mono" name="slug" value="{{ old('slug', $group->slug) }}" placeholder="bonus" required>
            <p class="mt-1 text-xs text-ink-muted">Hub opens at <code>/dashboard/tools/access/{slug}</code></p>
        </div>
        <div><label class="ui-label">Hub title</label><input class="ui-input" name="title" value="{{ old('title', $group->title) }}" placeholder="Bonus Tools" required></div>
        <div><label class="ui-label">Subtitle</label><input class="ui-input" name="subtitle" value="{{ old('subtitle', $group->subtitle) }}" placeholder="Extra tools included with your plan"></div>
        <div><label class="ui-label">Logo URL</label><input class="ui-input" name="logo_url" value="{{ old('logo_url', $group->logo_url) }}"></div>
        <div><label class="ui-label">Sort order</label><input class="ui-input" type="number" name="sort_order" value="{{ old('sort_order', $group->sort_order ?? 0) }}"></div>
        <label class="flex gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))> Active</label>
        <p class="text-xs text-ink-muted">After saving, add each bonus tool as a separate button in <a href="{{ route('admin.tool-servers.index') }}" class="text-accent">Access Servers</a>.</p>
        <button class="ui-btn-primary">Save Group</button>
    </form>
@endsection
