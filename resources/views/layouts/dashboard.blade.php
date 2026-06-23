<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon />
    <title>@yield('title', 'Dashboard') — Semrushtoolz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="dash-shell">
        <x-dashboard.sidebar :user="$user" :active="$activeNav ?? ''" />

        <div class="min-w-0 flex-1 pb-20 lg:pb-8">
            <x-dashboard.header :user="$user" :notifications="$notifications ?? []" />

            @if (session('success'))
                <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

    <x-whatsapp-float />

    @livewireScripts
    @stack('scripts')
</body>
</html>
