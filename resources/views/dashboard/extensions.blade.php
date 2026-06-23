@extends('layouts.dashboard')

@section('title', 'Extensions')

@section('content')
    <h1 class="dash-page-title">Chrome Extension</h1>

    @if (!$canDownload)
        <div class="dash-card text-center">
            <p class="text-sm text-ink-secondary">Subscribe to any plan to download and use the extension.</p>
            <a href="{{ route('dashboard.shop') }}" class="ui-btn-primary mt-4 inline-flex">View Plans</a>
        </div>
    @else
        <div class="dash-card">
            <div class="flex flex-wrap items-start gap-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-surface">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="21.17" y1="8" x2="12" y2="8"/><line x1="3.95" y1="6.06" x2="8.54" y2="14"/><line x1="10.88" y1="21.94" x2="15.46" y2="14"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-ink">Semrushtoolz Extension</h2>
                    <p class="mt-1 text-sm text-ink-secondary">Required for extension-based tools (Canva, UberSuggest, etc.)</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span id="ext-status-badge" class="dash-badge-offline">Checking...</span>
                        @if (!empty($config['version']))
                            <span class="text-xs text-ink-muted">Latest: v{{ $config['version'] }}</span>
                        @endif
                    </div>
                </div>
                @if (!empty($config['download_url']))
                    <a href="{{ $config['download_url'] }}" target="_blank" rel="noopener" class="ui-btn-primary">Download Extension</a>
                @endif
            </div>
        </div>

        @if (!empty($extensionTools))
            <div class="mt-6">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-ink-muted">Your Extension Tools</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($extensionTools as $tool)
                        @php $hubSlug = app(\App\Services\ToolAccessService::class)->hubSlugForTool($tool->slug); @endphp
                        @if ($hubSlug)
                            <a href="{{ route('dashboard.tools.hub', $hubSlug) }}" class="dash-card hover:border-accent">
                                <div class="font-semibold text-ink">{{ $tool->name }}</div>
                                <div class="text-xs text-ink-muted">Open access panel</div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($config['install_guide']))
            <div class="dash-card mt-6">
                <h3 class="dash-card-title">Install Guide</h3>
                <div class="prose-section text-sm text-ink-secondary">{!! $config['install_guide'] !!}</div>
            </div>
        @else
            <div class="dash-card mt-6">
                <h3 class="dash-card-title">Install Guide</h3>
                <ol class="space-y-3 text-sm text-ink-secondary">
                    <li class="flex gap-2"><span class="font-bold text-accent">1.</span> Download the Chrome extension</li>
                    <li class="flex gap-2"><span class="font-bold text-accent">2.</span> Open chrome://extensions and enable Developer mode</li>
                    <li class="flex gap-2"><span class="font-bold text-accent">3.</span> Load unpacked extension folder</li>
                    <li class="flex gap-2"><span class="font-bold text-accent">4.</span> Login with your Semrushtoolz account</li>
                </ol>
            </div>
        @endif

        <span id="{{ $config['indicator_id'] ?? 'my-extension-installed-indicator' }}" style="display:none" aria-hidden="true"></span>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const badge = document.getElementById('ext-status-badge');
                const id = @json($config['indicator_id'] ?? 'my-extension-installed-indicator');
                function check() {
                    const ok = !!document.getElementById(id);
                    if (!badge) return;
                    badge.textContent = ok ? 'Installed' : 'Not Installed';
                    badge.className = ok ? 'dash-badge-online' : 'dash-badge-offline';
                }
                check();
                let n = 0;
                const t = setInterval(() => { check(); if (++n > 10) clearInterval(t); }, 500);
            });
        </script>
    @endif
@endsection
