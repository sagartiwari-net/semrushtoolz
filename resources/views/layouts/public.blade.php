<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />

    @hasSection('seo_meta')
        @yield('seo_meta')
    @else
        <x-seo-meta
            title="@yield('title', 'Semrushtoolz — Premium SEO Tools at Fraction of the Cost')"
            description="@yield('meta_description', 'Semrushtoolz — Affordable group buy access to Semrush, Ahrefs & more. One-click cloud access, instant activation.')"
        />
    @endif

    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    <x-public-nav />

    <main>
        @yield('content')
    </main>

    <x-public-footer />

    <x-whatsapp-float />

    @livewireScripts
</body>
</html>
