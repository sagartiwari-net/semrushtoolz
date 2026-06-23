@extends('layouts.admin')

@section('title', 'Email Presets')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="dash-page-title">Email Presets</h1>
            <p class="text-sm text-ink-secondary">Edit the subject and body for each email type. Changes auto-sync to Mail Panel on save.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.email-settings.edit') }}" class="ui-btn ui-btn-outline">Email Setup</a>
            <a href="{{ route('admin.email-presets.create') }}" class="ui-btn ui-btn-primary">+ New Preset</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-5 rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink-secondary">
        Use variables like <code class="text-accent">@{{name}}</code>, <code class="text-accent">@{{otp}}</code>, <code class="text-accent">@{{plan_name}}</code>, etc.
        <strong class="mt-2 block text-ink">Edit designs here — they sync to Mail Panel on save and appear in live emails.</strong>
    </div>

    @php $presetCount = $presetsByCategory->flatten()->count(); @endphp

    @if ($presetCount === 0)
        <div class="dash-card text-center text-ink-muted">No presets yet. Run <code>php artisan db:seed --class=EmailPresetSeeder</code></div>
    @else
        @foreach ($categories as $categoryKey => $categoryLabel)
            @php $items = $presetsByCategory->get($categoryKey, collect()); @endphp
            @if ($items->isNotEmpty())
                <div class="mb-8">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">{{ $categoryLabel }}</h2>
                    <div class="dash-card overflow-hidden p-0">
                        <table class="w-full text-sm">
                            <thead class="border-b border-line bg-surface text-left text-xs uppercase text-ink-muted">
                                <tr>
                                    <th class="px-4 py-3">Preset</th>
                                    <th class="px-4 py-3">Slug</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Synced</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($items as $preset)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-ink">{{ $preset->name }}</div>
                                            <div class="text-xs text-ink-muted">{{ $preset->key }}</div>
                                        </td>
                                        <td class="px-4 py-3"><code class="text-xs">{{ $preset->slug }}</code></td>
                                        <td class="px-4 py-3">
                                            @if ($preset->is_enabled)
                                                <span class="rounded-full bg-success/10 px-2 py-0.5 text-xs text-success">Enabled</span>
                                            @else
                                                <span class="rounded-full bg-ink-muted/10 px-2 py-0.5 text-xs text-ink-muted">Disabled</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-ink-muted">
                                            {{ $preset->last_synced_at?->diffForHumans() ?? 'Never' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('admin.email-presets.edit', $preset) }}" class="text-accent hover:underline">Edit</a>
                                                <form method="POST" action="{{ route('admin.email-presets.sync', $preset) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-ink-muted hover:text-accent">Sync</button>
                                                </form>
                                                @unless ($preset->is_system)
                                                    <form method="POST" action="{{ route('admin.email-presets.destroy', $preset) }}" class="inline" onsubmit="return confirm('Delete this preset?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-danger hover:underline">Delete</button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@endsection
