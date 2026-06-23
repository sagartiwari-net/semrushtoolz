@extends('layouts.admin')

@section('title', 'Access Servers')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-ink-secondary">Manage access buttons shown on Semrush / Ahrefs hub pages. No code edit needed.</p>
            <p class="mt-1 text-xs text-ink-muted">Proxy buttons use route: <code>/dashboard/tools/route/{slug}</code></p>
        </div>
        <a href="{{ route('admin.tool-servers.create') }}" class="ui-btn-primary">+ Add Server</a>
    </div>

    @foreach ($groups as $group)
        <div class="dash-card mb-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-ink">{{ $group->title }}</h2>
                    <p class="text-xs text-ink-muted">Group: <code>{{ $group->slug }}</code> · Grant: {{ $group->grant }}</p>
                </div>
                <span @class(['dash-badge-online' => $group->is_active, 'dash-badge-offline' => !$group->is_active])>
                    {{ $group->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Slug / Route</th>
                            <th>Type</th>
                            <th>Domain / URL</th>
                            <th>Section</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($group->servers as $server)
                            <tr>
                                <td class="font-medium text-ink">{{ $server->label }}</td>
                                <td>
                                    @if ($server->isProxy())
                                        <code class="text-xs">{{ $server->slug }}</code>
                                    @else
                                        <span class="text-xs text-ink-muted">direct link</span>
                                    @endif
                                </td>
                                <td><span class="ui-badge bg-surface text-ink-secondary">{{ $server->type }}</span></td>
                                <td class="max-w-[200px] truncate text-xs" title="{{ $server->domain ?? $server->direct_url }}">
                                    {{ $server->domain ?? $server->direct_url ?? '—' }}
                                </td>
                                <td class="text-xs">{{ $server->section_title ?? '—' }}</td>
                                <td>{{ $server->sort_order }}</td>
                                <td>
                                    <span @class([
                                        'dash-badge-online' => $server->is_active,
                                        'dash-badge-offline' => !$server->is_active,
                                    ])>{{ $server->is_active ? 'On' : 'Off' }}</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('admin.tool-servers.edit', $server) }}" class="ui-btn-ghost text-xs">Edit</a>
                                    <form method="POST" action="{{ route('admin.tool-servers.toggle', $server) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="ui-btn-ghost text-xs">{{ $server->is_active ? 'Off' : 'On' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.tool-servers.destroy', $server) }}" class="inline" onsubmit="return confirm('Remove this server?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-btn-ghost text-xs text-danger">Del</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-ink-muted">No servers in this group.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
