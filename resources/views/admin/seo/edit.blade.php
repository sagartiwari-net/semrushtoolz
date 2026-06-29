@extends('layouts.admin')

@section('title', 'SEO & Sitemap')

@section('content')
    @php
        $tabs = [
            'sitemap' => 'Sitemap',
            'robots' => 'robots.txt',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="dash-page-title">SEO &amp; Sitemap</h1>
            <p class="text-sm text-ink-muted">Sitemap auto-updates when you publish or edit tool pages and legal pages.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $sitemapUrl }}" target="_blank" class="ui-btn-outline text-sm">View sitemap.xml</a>
            <a href="{{ $robotsUrl }}" target="_blank" class="ui-btn-outline text-sm">View robots.txt</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <x-dash-tabs :tabs="$tabs" :active="$tab" :preserve="[]">
        <div @class(['space-y-6', 'hidden' => $tab !== 'sitemap'])>
            <div class="dash-card space-y-4">
                <h2 class="font-semibold text-ink">Live sitemap preview</h2>
                <p class="text-xs text-ink-muted">
                    Tool pages (Admin → Tool Pages) and legal pages are included automatically with their latest <code>updated_at</code> date.
                    No manual action needed when you create or edit a page.
                </p>
                <div class="overflow-x-auto rounded-xl border border-line">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-line bg-surface/60 text-xs uppercase text-ink-muted">
                            <tr>
                                <th class="px-4 py-3">URL</th>
                                <th class="px-4 py-3">Source</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3">Last mod</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($sitemapUrls as $row)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs"><a href="{{ $row['loc'] }}" target="_blank" class="text-accent hover:underline">{{ $row['loc'] }}</a></td>
                                    <td class="px-4 py-2 text-ink-secondary">{{ $row['source'] }}</td>
                                    <td class="px-4 py-2">{{ $row['priority'] }}</td>
                                    <td class="px-4 py-2 text-ink-muted">{{ $row['lastmod'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-ink-muted">No URLs yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-ink-muted">{{ count($sitemapUrls) }} URLs total · served at <a href="{{ $sitemapUrl }}" class="text-accent hover:underline">{{ $sitemapUrl }}</a></p>
            </div>

            <form method="POST" action="{{ route('admin.seo.sitemap-extras') }}" class="dash-card space-y-4">
                @csrf
                @method('PUT')
                <h2 class="font-semibold text-ink">Extra URLs (optional)</h2>
                <p class="text-xs text-ink-muted">Add custom URLs that are not tool or legal pages (e.g. landing pages on another path).</p>
                @php $extras = old('extra_loc') ? collect(old('extra_loc'))->keys() : collect($extraUrls)->keys(); @endphp
                @for ($i = 0; $i < max(2, count($extraUrls) + 1); $i++)
                    <div class="grid gap-3 rounded-lg border border-line p-3 sm:grid-cols-[1fr_100px_120px]">
                        <input class="ui-input font-mono text-xs" name="extra_loc[]" value="{{ old('extra_loc.'.$i, $extraUrls[$i]['loc'] ?? '') }}" placeholder="https://yoursite.com/custom-page">
                        <input class="ui-input text-xs" name="extra_priority[]" value="{{ old('extra_priority.'.$i, $extraUrls[$i]['priority'] ?? '0.6') }}" placeholder="0.6">
                        <select class="ui-input text-xs" name="extra_changefreq[]">
                            @foreach (['daily', 'weekly', 'monthly', 'yearly'] as $freq)
                                <option value="{{ $freq }}" @selected(old('extra_changefreq.'.$i, $extraUrls[$i]['changefreq'] ?? 'monthly') === $freq)>{{ $freq }}</option>
                            @endforeach
                        </select>
                    </div>
                @endfor
                <button type="submit" class="ui-btn-primary">Save extra URLs</button>
            </form>
        </div>

        <div @class(['space-y-6 max-w-4xl', 'hidden' => $tab !== 'robots'])>
            <form method="POST" action="{{ route('admin.seo.robots') }}" class="dash-card space-y-4">
                @csrf
                @method('PUT')
                <h2 class="font-semibold text-ink">robots.txt</h2>
                <p class="text-xs text-ink-muted">Served dynamically at <a href="{{ $robotsUrl }}" target="_blank" class="text-accent hover:underline">{{ $robotsUrl }}</a>. Include a <code>Sitemap:</code> line pointing to your sitemap.</p>
                <textarea class="ui-input min-h-[280px] font-mono text-xs" name="robots_txt" rows="14" required>{{ old('robots_txt', $robotsTxt) }}</textarea>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="ui-btn-primary">Save robots.txt</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.seo.robots.reset') }}" onsubmit="return confirm('Reset robots.txt to default?')">
                @csrf
                <button type="submit" class="ui-btn-ghost text-sm text-danger">Reset to default</button>
            </form>
            <details class="dash-card text-xs text-ink-muted">
                <summary class="cursor-pointer font-medium text-ink">Default robots.txt</summary>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-surface p-3 font-mono">{{ $defaultRobots }}</pre>
            </details>
        </div>
    </x-dash-tabs>
@endsection
