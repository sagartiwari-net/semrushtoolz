<header class="sticky top-0 z-50 border-b border-line/80 bg-white/80 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <x-logo />

        <nav class="hidden items-center gap-1 md:flex">
            <a href="#plans" class="ui-btn-ghost px-3 py-2">Plans</a>
            <a href="#ahrefs-plans" class="ui-btn-ghost px-3 py-2">Ahrefs</a>
            <a href="#how-it-works" class="ui-btn-ghost px-3 py-2">How It Works</a>
            <a href="#faq" class="ui-btn-ghost px-3 py-2">FAQ</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="ui-btn-ghost hidden px-3 py-2 sm:inline-flex">Login</a>
            <a href="{{ route('register') }}" class="ui-btn-primary px-4 py-2 text-sm">Get Started</a>

            <div class="dropdown dropdown-end md:hidden">
                <button tabindex="0" class="btn btn-ghost btn-square btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <ul tabindex="0" class="menu dropdown-content z-[1] mt-2 w-52 rounded-xl border border-line bg-white p-2 shadow-lg">
                    <li><a href="#plans">Plans</a></li>
                    <li><a href="#ahrefs-plans">Ahrefs Plans</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="{{ route('login') }}">Login</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
