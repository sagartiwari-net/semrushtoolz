<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />

    @if (!empty($seo))
        <x-seo-meta
            :title="$seo['title']"
            :description="$seo['description']"
            :keywords="$seo['keywords'] ?? ''"
        />
    @else
        <x-seo-meta
            title="@yield('title', 'Account')"
            description="@yield('meta_description', 'Semrushtoolz account — access your Semrush and Ahrefs group buy dashboard.')"
        />
    @endif

    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen gradient-hero">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="mb-8">
            <x-logo />
        </a>

        @hasSection('seo_content')
            <div class="grid w-full gap-8 lg:grid-cols-2 lg:items-start">
                <div class="auth-card ui-card p-8 lg:p-10">
                    @yield('content')
                </div>
                <div>
                    @yield('seo_content')
                </div>
            </div>
        @else
            <div class="auth-card ui-card w-full max-w-md p-8 sm:p-10">
                @yield('content')
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-ink-muted">
            @yield('footer_link')
        </p>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
