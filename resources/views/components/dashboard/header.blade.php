@props(['user', 'notifications' => []])

<header class="ui-card relative mb-4 flex flex-wrap items-center gap-3 px-4 py-3 sm:px-5">
    <button type="button" id="mobile-header-menu" class="btn btn-ghost btn-sm btn-circle lg:hidden" aria-label="Open menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
    </button>

    <div class="relative min-w-0 flex-1 sm:max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" placeholder="Search tools, orders…" class="ui-input !rounded-full !py-2 pl-9">
    </div>

    <div class="ml-auto flex items-center gap-2">
        @if ($user['wallet_enabled'] ?? false)
            <a href="{{ route('dashboard.wallet') }}" class="hidden rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success hover:bg-success/20 sm:inline-flex">
                Wallet {{ $user['wallet_balance_label'] }}
            </a>
        @endif
        <span class="hidden rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent sm:inline-flex">
            {{ $user['plan'] }} Plan
        </span>

        <div class="dropdown dropdown-end">
            <button tabindex="0" class="btn btn-ghost btn-sm btn-circle relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                @if (collect($notifications)->where('unread', true)->count())
                    <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-danger ring-2 ring-white"></span>
                @endif
            </button>
            <div tabindex="0" class="dropdown-content z-50 mt-2 w-80 rounded-2xl border border-line bg-white p-2 shadow-lg">
                <div class="flex items-center justify-between border-b border-line px-3 py-2">
                    <h3 class="text-sm font-bold text-ink">Notifications</h3>
                    <button class="text-xs font-medium text-accent">Mark all read</button>
                </div>
                <div class="max-h-72 overflow-y-auto py-1">
                    @foreach ($notifications as $notif)
                        <div @class(['flex gap-3 rounded-xl px-3 py-2.5', 'bg-accent/5' => $notif['unread']])>
                            <div @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs',
                                'bg-warning/10 text-warning' => $notif['type'] === 'promo',
                                'bg-danger/10 text-danger' => $notif['type'] === 'warn',
                                'bg-blue/10 text-blue' => $notif['type'] === 'info',
                            ])>
                                @if ($notif['type'] === 'promo') 🏷 @elseif ($notif['type'] === 'warn') ⚠ @else ℹ @endif
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-ink">{{ $notif['title'] }}</div>
                                <div class="text-xs text-ink-muted">{{ $notif['time'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <a href="{{ route('dashboard.support') }}" class="btn btn-ghost btn-sm btn-circle hidden sm:flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
        </a>

        <div class="dropdown dropdown-end">
            <div tabindex="0" class="flex cursor-pointer items-center gap-2 rounded-full border border-line py-1 pl-1 pr-3">
                <x-dashboard.user-avatar :user="$user" size="xs" />
                <span class="hidden text-sm font-semibold sm:inline">{{ $user['name'] }}</span>
            </div>
            <ul tabindex="0" class="dropdown-content z-50 mt-2 w-48 rounded-xl border border-line bg-white p-2 shadow-lg">
                <li><a href="{{ route('dashboard.profile') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-surface">Profile</a></li>
                <li><a href="{{ route('dashboard.settings') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-surface">Settings</a></li>
                <li class="border-t border-line mt-1 pt-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-danger hover:bg-danger/5">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
