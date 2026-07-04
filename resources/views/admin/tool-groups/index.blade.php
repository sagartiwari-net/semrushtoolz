@extends('layouts.admin')

@section('title', 'Tool Groups')

@section('content')
    <div class="mb-5 flex justify-between">
        <p class="text-sm text-ink-secondary">Each tool needs an access group (hub page). Example: Envato → envato group → access buttons.</p>
        <a href="{{ route('admin.tool-groups.create') }}" class="ui-btn-primary">+ Add Group</a>
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Title</th><th>Slug</th><th>Tool</th><th>Hub URL</th><th></th></tr></thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr>
                        <td>{{ $group->title }}</td>
                        <td><code>{{ $group->slug }}</code></td>
                        <td>{{ $group->tool?->name ?? $group->grant }}</td>
                        <td class="text-xs">/dashboard/tools/access/{{ $group->slug }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.tool-groups.edit', $group) }}" class="ui-btn-ghost text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.tool-groups.destroy', $group) }}" class="inline" onsubmit="return confirm('Delete group {{ $group->title }}? Access servers under it will also be removed.')">
                                @csrf
                                @method('DELETE')
                                <button class="ui-btn-ghost text-xs text-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
