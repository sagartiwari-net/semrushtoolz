<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />

    <x-seo-meta
        :title="$seo['title']"
        :description="$seo['description']"
        :keywords="$seo['keywords'] ?? ''"
        :canonical="$article->publicUrl()"
        og-type="product"
        :og-image="$article->resolvedHeroImage()"
    />

    @if (!empty($isPreview))
        <meta name="robots" content="noindex, nofollow">
    @endif

    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas">
    <x-public-nav />

    <main>
        @yield('content')
    </main>

    <x-public-footer />

    @livewireScripts
</body>
</html>
