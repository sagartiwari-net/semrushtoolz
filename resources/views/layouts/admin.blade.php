<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />
    <title>@yield('title', 'Admin') — Semrushtoolz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="dash-shell">
        <x-admin.sidebar />

        <div class="min-w-0 flex-1">
            <header class="ui-card mb-4 flex items-center justify-between px-5 py-3">
                <div>
                    <h2 class="text-sm font-bold text-ink">@yield('title', 'Admin Panel')</h2>
                    <p class="text-xs text-ink-muted">Semrushtoolz administration</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="dash-badge-online">System Online</span>
                    <a href="{{ route('admin.profile') }}" class="ui-btn-ghost text-xs">Profile</a>
                    <a href="{{ route('home') }}" class="ui-btn-ghost text-xs">View Site</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="ui-btn-ghost text-xs text-danger">Logout</button>
                    </form>
                </div>
            </header>

            @if (session('success'))
                <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
