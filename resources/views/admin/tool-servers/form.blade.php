@extends('layouts.admin')

@section('title', $server->exists ? 'Edit Access Server' : 'Add Access Server')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.tool-servers.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Access Servers</a>
        <h1 class="dash-page-title mt-2">{{ $server->exists ? 'Edit Server' : 'Add Server' }}</h1>
    </div>

    <form method="POST"
        action="{{ $server->exists ? route('admin.tool-servers.update', $server) : route('admin.tool-servers.store') }}"
        class="dash-card max-w-2xl space-y-5"
        id="server-form">
        @csrf
        @if ($server->exists)
            @method('PUT')
        @endif

        <div>
            <label class="ui-label">Tool group</label>
            <select class="ui-input" name="tool_access_group_id" required>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected(old('tool_access_group_id', $server->tool_access_group_id) == $group->id)>
                        {{ $group->title }} ({{ $group->slug }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="ui-label">Button label</label>
                <input class="ui-input" name="label" value="{{ old('label', $server->label) }}" placeholder="Access Semrush 12" required>
            </div>
            <div>
                <label class="ui-label">Slug (route key)</label>
                <input class="ui-input font-mono" name="slug" value="{{ old('slug', $server->slug) }}" placeholder="nnxsm12" required>
                <p class="mt-1 text-xs text-ink-muted">Used in <code>/dashboard/tools/route/{slug}</code></p>
            </div>
        </div>

        <div>
            <label class="ui-label">Type</label>
            <select class="ui-input" name="type" id="server-type" required>
                <option value="proxy" @selected(old('type', $server->type) === 'proxy')>Cloud Proxy (one-click handshake)</option>
                <option value="direct" @selected(old('type', $server->type) === 'direct')>Direct URL</option>
                <option value="extension" @selected(old('type', $server->type) === 'extension')>Extension button (POST to ext API)</option>
            </select>
        </div>

        <div id="extension-fields" class="space-y-4 hidden">
            <div>
                <label class="ui-label">Extension tool key</label>
                <input class="ui-input font-mono" name="extension_tool_key" value="{{ old('extension_tool_key', $server->extension_tool_key) }}" placeholder="canva1">
                <p class="mt-1 text-xs text-ink-muted">Sent to extension API as <code>tool</code> — same as data-tool in aMember</p>
            </div>
        </div>

        <div id="proxy-fields" class="space-y-4">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Proxy domain</label>
                    <input class="ui-input" name="domain" value="{{ old('domain', $server->domain) }}" placeholder="nnxsm1.1clkaccess.store">
                </div>
                <div>
                    <label class="ui-label">Website ID</label>
                    <input class="ui-input" type="number" name="website_id" value="{{ old('website_id', $server->website_id) }}" placeholder="10">
                </div>
            </div>
            <div>
                <label class="ui-label">Secret key (HMAC)</label>
                <input class="ui-input font-mono text-sm" name="secret_key" value="{{ old('secret_key', $server->secret_key) }}" placeholder="Leave blank to use group default">
            </div>
        </div>

        <div id="direct-fields">
            <label class="ui-label">Direct URL</label>
            <input class="ui-input" name="direct_url" value="{{ old('direct_url', $server->direct_url) }}" placeholder="https://...">
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="ui-label">Section title (optional)</label>
                <input class="ui-input" name="section_title" value="{{ old('section_title', $server->section_title) }}" placeholder="For Export only">
                <p class="mt-1 text-xs text-ink-muted">Groups buttons under a heading on hub page</p>
            </div>
            <div>
                <label class="ui-label">Sort order</label>
                <input class="ui-input" type="number" name="sort_order" value="{{ old('sort_order', $server->sort_order ?? 0) }}" min="0">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $server->is_active ?? true))> Active (show on hub page)
        </label>

        <button type="submit" class="ui-btn-primary">Save Server</button>
    </form>

    <script>
        const typeSelect = document.getElementById('server-type');
        const proxyFields = document.getElementById('proxy-fields');
        const directFields = document.getElementById('direct-fields');
        const extensionFields = document.getElementById('extension-fields');
        function toggleFields() {
            const type = typeSelect.value;
            proxyFields.style.display = type === 'proxy' ? 'block' : 'none';
            directFields.style.display = type === 'direct' ? 'block' : 'none';
            extensionFields.style.display = type === 'extension' ? 'block' : 'none';
        }
        typeSelect.addEventListener('change', toggleFields);
        toggleFields();
    </script>
@endsection
