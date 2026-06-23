@extends('layouts.dashboard')

@section('title', $hub['title'] ?? 'Tool Access')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.tools') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Back to My Tools</a>
    </div>

    <div class="dash-card text-center">
        @if (!empty($hub['logo']))
            <img src="{{ $hub['logo'] }}" alt="" class="mx-auto mb-4 h-14 w-14 object-contain">
        @endif
        <h1 class="text-2xl font-extrabold text-ink">{{ $hub['title'] }}</h1>
        @if (!empty($hub['subtitle']))
            <p class="mt-2 text-sm text-ink-muted">{{ $hub['subtitle'] }}</p>
        @endif
    </div>

    @php $accessType = $hub['access_type'] ?? 'cloud'; @endphp

    {{-- WhatsApp activation (Ahrefs Bar style) --}}
    @if ($accessType === 'whatsapp')
        <div class="mt-6 rounded-xl border border-[#25d366] bg-[#f0fdf4] p-5 sm:flex sm:items-center sm:justify-between sm:gap-4">
            <p class="text-base font-semibold text-[#166534]">{{ $hub['whatsapp_message'] ?? 'Contact us on WhatsApp for activation' }}</p>
            @if (!empty($hub['whatsapp_number']))
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $hub['whatsapp_number']) }}" target="_blank" rel="noopener"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#25d366] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#128c7e] sm:mt-0">
                    WhatsApp Us
                </a>
            @endif
        </div>
    @endif

    {{-- Username & password credentials --}}
    @if ($accessType === 'credentials')
        <div class="mt-6 space-y-4">
            @forelse ($hub['credentials'] ?? [] as $cred)
                <div class="dash-card">
                    @if (!empty($cred['label']))
                        <h3 class="mb-3 font-semibold text-ink">{{ $cred['label'] }}</h3>
                    @endif
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-ink-muted">Username</label>
                            <div class="mt-1 flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2">
                                <code class="flex-1 truncate text-sm">{{ $cred['username'] }}</code>
                                <button type="button" class="ui-btn-ghost text-xs copy-btn" data-copy="{{ $cred['username'] }}">Copy</button>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-ink-muted">Password</label>
                            <div class="mt-1 flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2">
                                <code class="flex-1 truncate text-sm">{{ $cred['password'] }}</code>
                                <button type="button" class="ui-btn-ghost text-xs copy-btn" data-copy="{{ $cred['password'] }}">Copy</button>
                            </div>
                        </div>
                    </div>
                    @if (!empty($cred['official_url']))
                        <a href="{{ $cred['official_url'] }}" target="_blank" rel="noopener" class="ui-btn-primary mt-4 inline-flex">Open Official Website</a>
                    @endif
                </div>
            @empty
                <div class="dash-card text-center text-sm text-ink-muted">Credentials are being set up. Please contact support.</div>
            @endforelse
        </div>
        <script>document.querySelectorAll('.copy-btn').forEach(b => b.addEventListener('click', () => navigator.clipboard.writeText(b.dataset.copy)));</script>
    @endif

    {{-- Extension tools: install check + access buttons --}}
    @if ($accessType === 'extension')
        @php
            $ext = $hub['extension'] ?? [];
            $downloadUrl = $hub['extension_download_url'] ?: ($ext['download_url'] ?? '');
            $indicatorId = $ext['indicator_id'] ?? 'my-extension-installed-indicator';
        @endphp

        <div id="ext-not-installed" class="mt-6 hidden">
            <div class="dash-card border-2 border-dashed border-accent/40 bg-surface text-center">
                <h2 class="text-lg font-bold text-ink">Extension Required</h2>
                <p class="mt-2 text-sm text-ink-secondary">Install our Chrome extension to access these tools.</p>
                @if ($downloadUrl)
                    <a href="{{ $downloadUrl }}" target="_blank" rel="noopener" class="ui-btn-primary mt-4 inline-flex">Install Extension</a>
                @endif
            </div>
        </div>

        <div id="ext-installed" class="mt-6 hidden">
            @foreach ($hub['sections'] as $section)
                @if (!empty($section['title']))
                    <h2 class="mb-4 text-center text-lg font-bold text-ink">{{ $section['title'] }}</h2>
                @endif
                <div class="tool-access-grid">
                    @foreach ($section['buttons'] as $button)
                        <form method="POST" action="{{ route('dashboard.tools.ext', $button['slug']) }}" target="_blank" class="contents">
                            @csrf
                            <button type="submit" class="tool-access-btn w-full">⚡ {{ $button['label'] }}</button>
                        </form>
                    @endforeach
                </div>
            @endforeach
        </div>

        <span id="{{ $indicatorId }}" style="display:none" aria-hidden="true"></span>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const indicatorId = @json($indicatorId);
                const installed = document.getElementById('ext-installed');
                const notInstalled = document.getElementById('ext-not-installed');
                function check() {
                    const ok = !!document.getElementById(indicatorId);
                    if (installed) installed.classList.toggle('hidden', !ok);
                    if (notInstalled) notInstalled.classList.toggle('hidden', ok);
                }
                check();
                let n = 0;
                const t = setInterval(() => { check(); if (++n > 10) clearInterval(t); }, 500);
            });
        </script>
    @endif

    {{-- Cloud tools: proxy + direct buttons --}}
    @if ($accessType === 'cloud')
        @foreach ($hub['sections'] as $section)
            @if (!empty($section['title']))
                <h2 class="mb-4 mt-8 text-center text-lg font-bold text-ink">{{ $section['title'] }}</h2>
            @endif

            <div class="tool-access-grid">
                @foreach ($section['buttons'] as $button)
                    @if (($button['type'] ?? 'proxy') === 'direct')
                        <a href="{{ $button['url'] }}" target="_blank" rel="noopener" class="tool-access-btn">
                            ⚡ {{ $button['label'] }}
                        </a>
                    @else
                        <a href="{{ route('dashboard.tools.route', $button['slug']) }}" target="_blank" rel="noopener" class="tool-access-btn">
                            ⚡ {{ $button['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endforeach

        <div class="mt-8 rounded-xl border border-line bg-surface px-4 py-3 text-center text-sm text-ink-muted">
            If a server shows "seats full", try another button above.
        </div>
    @endif
@endsection
