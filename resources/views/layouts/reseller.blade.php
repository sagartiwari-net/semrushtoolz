<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon />
    <title>@yield('title', 'Reseller') — Semrushtoolz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="dash-shell">
        <aside class="island-sidebar sticky top-4 flex h-[calc(100vh-32px)]">
            <div class="border-b border-line px-4 py-5">
                <a href="{{ route('reseller.index') }}" class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-ink py-1.5 pl-2 pr-3.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent text-white text-xs font-bold">R</span>
                        <span class="text-sm font-bold text-white">Reseller</span>
                    </span>
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-ink-muted">Panel</p>
                @php
                    $items = [
                        ['label' => 'Dashboard', 'route' => 'reseller.index', 'match' => 'reseller.index'],
                        ['label' => 'Provision access', 'route' => 'reseller.provision.create', 'match' => 'reseller.provision.*'],
                        ['label' => 'My users', 'route' => 'reseller.users.index', 'match' => 'reseller.users.index'],
                        ['label' => 'Reset password', 'route' => 'reseller.password.create', 'match' => 'reseller.password.*'],
                        ['label' => 'Balance', 'route' => 'reseller.balance.index', 'match' => 'reseller.balance.*'],
                        ['label' => 'Reports', 'route' => 'reseller.reports.index', 'match' => 'reseller.reports.*'],
                        ['label' => 'My profile', 'route' => 'reseller.profile', 'match' => 'reseller.profile*'],
                    ];
                @endphp
                @foreach ($items as $item)
                    @php $isActive = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" @class([
                        'dash-nav-item',
                        'dash-nav-item-active' => $isActive,
                        'dash-nav-item-inactive' => ! $isActive,
                    ])>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-line p-3">
                <div class="mb-2 truncate px-1 text-xs text-ink-muted">{{ auth()->user()->email }}</div>
                <form method="POST" action="{{ url('/logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-danger hover:bg-danger/5">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1 space-y-1 px-1 pb-8 sm:px-0">
            <header class="ui-card mb-5 flex items-center justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="text-sm font-bold text-ink">@yield('title', 'Reseller Panel')</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Manage access for your customers</p>
                </div>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="ui-btn-ghost text-xs text-danger">Logout</button>
                </form>
            </header>

            @if (session('success'))
                <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="space-y-1">
                @yield('content')
            </div>
        </div>
    </div>

    @if (session('credentials'))
        @php $cred = session('credentials'); @endphp
        <div id="reseller-cred-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-surface p-6 shadow-xl">
                <h3 class="text-lg font-bold text-ink">Share these credentials</h3>
                <p class="mt-1 text-sm text-ink-secondary">Copy and send to your customer. This password is shown once.</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <label class="text-xs text-ink-muted">Access URL</label>
                        <div class="mt-1 flex gap-2">
                            <input id="cred-url" type="text" readonly value="{{ $cred['access_url'] }}" class="ui-input flex-1 font-mono text-xs">
                            <button type="button" class="ui-btn-ghost text-xs" data-copy="cred-url">Copy</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-ink-muted">Email</label>
                        <div class="mt-1 flex gap-2">
                            <input id="cred-email" type="text" readonly value="{{ $cred['email'] }}" class="ui-input flex-1 font-mono text-xs">
                            <button type="button" class="ui-btn-ghost text-xs" data-copy="cred-email">Copy</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-ink-muted">Password</label>
                        <div class="mt-1 flex gap-2">
                            <input id="cred-pass" type="text" readonly value="{{ $cred['password'] }}" class="ui-input flex-1 font-mono text-xs">
                            <button type="button" class="ui-btn-ghost text-xs" data-copy="cred-pass">Copy</button>
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="cred-copy-all" class="ui-btn-ghost text-xs">Copy all</button>
                    <button type="button" id="cred-close" class="ui-btn-primary text-xs">Done</button>
                </div>
            </div>
        </div>
        <script>
            (function () {
                const modal = document.getElementById('reseller-cred-modal');
                document.querySelectorAll('[data-copy]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const el = document.getElementById(btn.getAttribute('data-copy'));
                        await navigator.clipboard.writeText(el.value);
                        btn.textContent = 'Copied';
                        setTimeout(() => btn.textContent = 'Copy', 1200);
                    });
                });
                document.getElementById('cred-copy-all')?.addEventListener('click', async () => {
                    const text = [
                        'Access URL: ' + document.getElementById('cred-url').value,
                        'Email: ' + document.getElementById('cred-email').value,
                        'Password: ' + document.getElementById('cred-pass').value,
                    ].join('\n');
                    await navigator.clipboard.writeText(text);
                    const btn = document.getElementById('cred-copy-all');
                    btn.textContent = 'Copied';
                    setTimeout(() => btn.textContent = 'Copy all', 1200);
                });
                document.getElementById('cred-close')?.addEventListener('click', () => modal.remove());
            })();
        </script>
    @endif

    @stack('scripts')
</body>
</html>
