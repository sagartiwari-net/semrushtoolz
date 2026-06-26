@extends('layouts.admin')

@section('title', 'Email Setup')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="dash-page-title">Email Setup</h1>
            <p class="text-sm text-ink-secondary">Mail Panel API connect karo — OTP, signup, payments, plan expiry sab yahan se manage hoga.</p>
        </div>
        <a href="{{ route('admin.email-presets.index') }}" class="ui-btn ui-btn-outline">Manage Presets →</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    @if ($config['enabled'] && ! $mailReady)
        <div class="mb-5 rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning">
            <strong>Email abhi active nahi hai.</strong>
            @if (! $hasApiKey)
                API key save nahi hui — neeche <strong>mk_...</strong> paste karke Save karo.
            @else
                Mail Panel URL check karo.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('admin.email-settings.update') }}" class="dash-card max-w-2xl space-y-5 mb-6">
        @csrf
        @method('PUT')

        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="enabled" value="1" class="rounded" @checked(old('enabled', $config['enabled']))>
            <span><strong>Enable Mail Panel</strong> — jab off ho, Laravel local mail fallback use hoga</span>
        </label>

        <div>
            <label class="ui-label">Mail Panel URL</label>
            <input class="ui-input font-mono text-sm" name="url" value="{{ old('url', $config['url']) }}" required placeholder="https://email.sagartiwari.net">
            <p class="mt-1 text-xs text-ink-muted">Central mail server — SemrushToolz kisi bhi server se is URL ko call karega.</p>
        </div>

        <div>
            <label class="ui-label">API Key</label>
            <input class="ui-input font-mono text-sm" name="api_key" value="" placeholder="{{ $hasApiKey ? '•••••••••••••••• (saved — change karne ke liye naya paste karo)' : 'mk_...' }}" autocomplete="off">
            <p class="mt-1 text-xs text-ink-muted">email.sagartiwari.net → Domains / API Keys se copy karo. From address API key ke domain se aata hai.</p>
        </div>

        <button type="submit" class="ui-btn ui-btn-primary">Save Email Setup</button>
    </form>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="dash-card space-y-4">
            <h2 class="text-base font-semibold text-ink">Daily send cap</h2>
            <p class="text-sm text-ink-secondary">
                Mail Panel par is API key ke liye kitni emails roz bhej sakte ho.
                <span class="block mt-1 text-xs text-ink-muted">Mail Panel alag server par hai (<strong>email.sagartiwari.net</strong>) — cap wahan update hoti hai.</span>
                @if ($mailReady && ! empty($emailStats))
                    Abhi: <strong>{{ $emailStats['sent_today'] ?? 0 }}</strong> bheji /
                    cap <strong>{{ $emailStats['daily_limit'] ?? $emailStats['daily_cap'] ?? '?' }}</strong>
                    ({{ $emailStats['remaining'] ?? '?' }} bachi).
                @endif
            </p>

            @if (session('error') && str_contains(session('error'), 'settings'))
                <div class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-ink-secondary">
                    <strong>Quick fix (bina API deploy):</strong> Login to
                    <a href="https://email.sagartiwari.net" class="text-accent hover:underline" target="_blank" rel="noopener">email.sagartiwari.net</a>
                    → <strong>Tenants</strong> → apna account → <strong>Daily limit = 1000</strong> → Save.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.email-settings.daily-limit') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @method('PUT')
                <div class="min-w-[10rem] flex-1">
                    <label class="ui-label">Daily cap (max 10,000)</label>
                    <input class="ui-input" type="number" name="daily_limit" min="1" max="10000"
                        value="{{ old('daily_limit', $emailStats['daily_limit'] ?? $emailStats['daily_cap'] ?? 1000) }}" required>
                </div>
                <button type="submit" class="ui-btn ui-btn-primary shrink-0" @disabled(! $mailReady)>Update Cap</button>
            </form>
        </div>

        <div class="dash-card space-y-4">
            <h2 class="text-base font-semibold text-ink">Test & Sync</h2>
            <p class="text-sm text-ink-secondary">Connection check karo aur saare enabled presets Mail Panel par push karo.</p>

            <form method="POST" action="{{ route('admin.email-settings.test-connection') }}" class="inline">
                @csrf
                <button type="submit" class="ui-btn ui-btn-outline">Test Connection</button>
            </form>

            <form method="POST" action="{{ route('admin.email-settings.sync-all') }}" class="inline">
                @csrf
                <button type="submit" class="ui-btn ui-btn-outline">Sync All Presets to Mail Panel</button>
            </form>

            <form method="POST" action="{{ route('admin.email-settings.test-send') }}" class="space-y-3 border-t border-line pt-4">
                @csrf
                <label class="ui-label">Send test OTP email</label>
                <div class="flex gap-2">
                    <input class="ui-input flex-1" type="email" name="test_email" placeholder="you@example.com" required>
                    <button type="submit" class="ui-btn ui-btn-primary shrink-0">Send Test</button>
                </div>
            </form>
        </div>

        <div class="dash-card space-y-3">
            <h2 class="text-base font-semibold text-ink">Remote Templates (Mail Panel)</h2>
            @if ($remoteTemplates->isEmpty())
                <p class="text-sm text-ink-muted">Koi template nahi mila — pehle connection test karo, phir presets sync karo.</p>
            @else
                <ul class="max-h-64 space-y-2 overflow-y-auto text-sm">
                    @foreach ($remoteTemplates as $template)
                        <li class="flex items-center justify-between rounded-lg bg-surface px-3 py-2">
                            <code>{{ $template['slug'] ?? '' }}</code>
                            <span class="text-xs text-ink-muted">{{ $template['scope'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
