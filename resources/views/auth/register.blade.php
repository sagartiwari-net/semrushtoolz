@extends('layouts.guest')

@section('content')
    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-accent/20 bg-accent/5 px-3 py-1 text-xs font-semibold text-accent">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
        Free account · Instant activation
    </div>

    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Create your account</h1>
    <p class="mt-2 text-sm leading-relaxed text-ink-secondary">
        Sign up to <strong class="text-ink">buy Semrush</strong> and <strong class="text-ink">buy Ahrefs</strong> at cheap price. One-click cloud access from your dashboard.
    </p>

    <form class="relative mt-8 space-y-5" action="{{ route('register.submit') }}" method="POST">
        @csrf

        @if ($errors->any())
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="ui-label" for="name">Full name</label>
            <input class="ui-input" type="text" id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required autocomplete="name">
        </div>

        <div>
            <label class="ui-label" for="email">Email address</label>
            <input class="ui-input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="ui-label" for="password">Password</label>
                <input class="ui-input" type="password" id="password" name="password" placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
            </div>
            <div>
                <label class="ui-label" for="password_confirmation">Confirm password</label>
                <input class="ui-input" type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat password" required minlength="8" autocomplete="new-password">
            </div>
        </div>

        @if ($signupBonus)
            <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-ink-secondary">
                <strong class="text-success">Referral bonus unlocked!</strong>
                @if ($signupBonus['referrer_name'])
                    You signed up through <strong class="text-ink">{{ $signupBonus['referrer_name'] }}</strong>'s link.
                @endif
                <p class="mt-1">
                    Get <strong>{{ $signupBonus['percent'] }}% extra off</strong> your first <strong>1-month</strong> purchase.
                    Valid for <strong>{{ $signupBonus['days'] }} days</strong> only — stackable with coupon codes.
                </p>
            </div>
        @endif

        {{-- Honeypot for bots — no visible label --}}
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="pointer-events-none absolute h-0 w-0 opacity-0" aria-hidden="true">

        @if ($turnstileEnabled ?? false)
            <div class="rounded-xl border border-line bg-surface/50 px-4 py-3">
                <p class="mb-2 text-xs font-medium text-ink-muted">Verify you are human</p>
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light"></div>
                @error('captcha')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-surface/60 p-4">
            <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-line text-accent focus:ring-accent" name="terms" value="1" required>
            <span class="text-sm leading-relaxed text-ink-secondary">
                I agree to the
                <a href="{{ route('legal.terms') }}" class="font-medium text-accent hover:underline">Terms of Service</a>
                and
                <a href="{{ route('legal.privacy') }}" class="font-medium text-accent hover:underline">Privacy Policy</a>
            </span>
        </label>

        <button type="submit" class="ui-btn-primary w-full py-3 text-base">
            Create Account
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-ink-muted">
        <a href="{{ url('/#plans') }}" class="text-accent hover:underline">View Plans</a>
        ·
        <a href="{{ url('/tools/semrush-group-buy') }}" class="text-accent hover:underline">Semrush Group Buy</a>
        ·
        <a href="{{ url('/tools/ahrefs-group-buy') }}" class="text-accent hover:underline">Ahrefs Group Buy</a>
    </p>
@endsection

@section('seo_content')
    <div class="ui-card p-8 lg:sticky lg:top-8">
        <h2 class="text-xl font-bold text-ink">Start with Premium SEO Tools Today</h2>
        <p class="mt-3 text-sm leading-relaxed text-ink-secondary">
            Join <strong>2,500+ SEO professionals</strong> on Semrushtoolz — India's trusted
            <strong>Semrush group buy</strong> and <strong>Ahrefs group buy</strong> platform.
            Create your account in under 2 minutes and subscribe to any plan instantly.
        </p>

        <div class="mt-6 grid grid-cols-2 gap-3">
            @foreach ([
                ['₹149', 'Semrush from'],
                ['₹699', 'Ahrefs from'],
                ['₹799', 'Combo plan'],
                ['24/7', 'Support'],
            ] as $stat)
                <div class="rounded-xl border border-line bg-surface/60 px-4 py-3 text-center">
                    <div class="text-lg font-bold text-ink">{{ $stat[0] }}</div>
                    <div class="text-xs text-ink-muted">{{ $stat[1] }}</div>
                </div>
            @endforeach
        </div>

        <h3 class="mt-8 font-semibold text-ink">What you get after signup:</h3>
        <ul class="mt-4 space-y-3 text-sm text-ink-secondary">
            @foreach ([
                'One-click Semrush & Ahrefs cloud access',
                'Pay via UPI, PayPal, or offline payment',
                '1, 3, 6, or 12-month plans with discounts',
                'Manage subscriptions from your dashboard',
                'Ahrefs Bar extension on combo plan',
            ] as $item)
                <li class="flex items-start gap-2">
                    <svg class="mt-0.5 shrink-0 text-success" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                    {{ $item }}
                </li>
            @endforeach
        </ul>

        <div class="mt-8 rounded-xl bg-gradient-to-br from-ink to-ink/90 p-5 text-white">
            <p class="text-sm font-semibold">Best Value — Combo Plan</p>
            <p class="mt-1 text-2xl font-bold">₹799<span class="text-sm font-normal text-white/60">/month</span></p>
            <p class="mt-2 text-xs leading-relaxed text-white/70">
                Semrush + Ahrefs Plan 1 + Ahrefs Bar extension. Save up to 20% on annual billing.
            </p>
            <a href="{{ url('/#plans') }}" class="ui-btn-primary mt-4 w-full py-2.5 text-sm">Compare All Plans</a>
        </div>

        <p class="mt-6 text-xs leading-relaxed text-ink-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Sign in to dashboard</a>
        </p>
    </div>
@endsection

@push('schema')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Sign Up — Buy Semrush & Ahrefs Group Buy',
    'description' => 'Create your Semrushtoolz account to buy Semrush and Ahrefs at cheap price.',
    'url' => route('register'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('footer_link')
    Already have an account? <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Sign in</a>
@endsection

@if ($turnstileEnabled ?? false)
    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif
