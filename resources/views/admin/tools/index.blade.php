@extends('layouts.admin')

@section('title', 'Tools')

@section('content')
    <div class="mb-5 flex justify-between gap-3">
        <p class="text-sm text-ink-secondary">Add tools here with price and access type. Then link them in Plans.</p>
        <a href="{{ route('admin.tools.create') }}" class="ui-btn-primary">+ Add Tool</a>
    </div>

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Tool</th><th>Category</th><th>Price</th><th>Hub</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($tools as $tool)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($tool->thumbnailUrl())
                                    <img src="{{ $tool->thumbnailUrl() }}" alt="" class="h-8 w-8 rounded border border-line bg-white object-contain p-0.5">
                                @endif
                                <div>
                                    <div class="font-medium text-ink">{{ $tool->name }}</div>
                                    <code class="text-xs">{{ $tool->slug }}</code>
                                </div>
                            </div>
                        </td>
                        <td><span class="ui-badge bg-surface text-ink-secondary">{{ $tool->categoryLabel() }}</span></td>
                        <td class="text-sm">
                            @if ($tool->price_inr) ₹{{ $tool->price_inr }} @endif
                            @if ($tool->price_usd) / ${{ $tool->price_usd }} @endif
                        </td>
                        <td>{{ $tool->accessGroup?->slug ?? '—' }}</td>
                        <td><span @class(['dash-badge-online' => $tool->is_active, 'dash-badge-offline' => !$tool->is_active])>{{ $tool->is_active ? 'On' : 'Off' }}</span></td>
                        <td>
                            <a href="{{ route('admin.tools.preview', $tool) }}" target="_blank" class="ui-btn-ghost text-xs">Preview</a>
                            <a href="{{ route('admin.tools.edit', $tool) }}" class="ui-btn-ghost text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.tools.toggle', $tool) }}" class="inline">@csrf<button class="ui-btn-ghost text-xs">Toggle</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
