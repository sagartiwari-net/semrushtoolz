@extends('layouts.guest')

@section('content')
    @php
        $activeMode = $loginMode ?? 'password';
        $otpEmail = $otpEmail ?? null;
        $showOtpVerify = $activeMode === 'otp-verify' && filled($otpEmail);
        $showPassword = ! $showOtpVerify && $activeMode !== 'otp';
        $showOtpSend = ! $showOtpVerify && $activeMode === 'otp';
    @endphp

    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Semrush Login &amp; Ahrefs Login</h1>
    <p class="mt-2 text-sm text-ink-secondary">Sign in to your Semrushtoolz group buy dashboard</p>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @if (session('email_spam_tip'))
            <x-email-spam-tip class="mt-3" />
        @endif
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            {{ $errors->first() }}
            @if ($errors->has('email') && str_contains($errors->first('email'), 'No account found'))
                <div class="mt-2">
                    <a href="{{ route('register') }}" class="font-semibold underline">Create an account →</a>
                </div>
            @endif
        </div>
    @endif

    <div class="mt-6 flex rounded-xl border border-line bg-surface p-1" id="login-tabs">
        <button type="button" id="tab-password" @class([
            'flex-1 rounded-lg px-3 py-2 text-sm font-semibold',
            'bg-accent text-white' => $showPassword,
            'text-ink-secondary' => ! $showPassword,
        ])>Password</button>
        <button type="button" id="tab-otp" @class([
            'flex-1 rounded-lg px-3 py-2 text-sm font-semibold',
            'bg-accent text-white' => ! $showPassword,
            'text-ink-secondary' => $showPassword,
        ])>Email OTP</button>
    </div>

    <form id="form-password" @class(['mt-6 space-y-5', 'hidden' => ! $showPassword]) action="{{ route('login.submit') }}" method="POST">
        @csrf
        <div>
            <label class="ui-label" for="email">Email address</label>
            <input class="ui-input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label class="ui-label mb-0" for="password">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-medium text-accent hover:underline">Forgot password?</a>
            </div>
            <input class="ui-input" type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <label class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" name="remember" value="1">
            <span class="text-sm text-ink-secondary">Remember me</span>
        </label>

        <button type="submit" class="ui-btn-primary w-full py-3">Sign In to Dashboard</button>
    </form>

    <form id="form-otp-send" @class(['mt-6 space-y-5', 'hidden' => ! $showOtpSend]) action="{{ route('login.otp.send') }}" method="POST">
        @csrf
        <div>
            <label class="ui-label" for="otp_email">Email address</label>
            <input class="ui-input" type="email" id="otp_email" name="email" value="{{ old('email', $otpEmail) }}" placeholder="you@example.com" required autocomplete="email">
        </div>
        <p class="text-xs text-ink-muted">Registered email only. We'll send a 6-digit code — valid for {{ config('auth_otp.expires_minutes', 10) }} minutes. Temp/disposable emails are not allowed.</p>
        <button type="submit" class="ui-btn-primary w-full py-3">Send login code</button>
    </form>

    <div id="form-otp-verify" @class(['mt-6 space-y-5', 'hidden' => ! $showOtpVerify])>
        <div class="rounded-xl border border-accent/20 bg-accent/5 px-4 py-3 text-sm text-ink-secondary">
            Code sent to <strong class="text-ink">{{ $otpEmail }}</strong>
        </div>

        <x-email-spam-tip />

        <form class="space-y-5" action="{{ route('login.otp.verify') }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="{{ $otpEmail }}">
            <div>
                <label class="ui-label" for="code">6-digit code</label>
                <input class="ui-input text-center font-mono text-lg tracking-widest" type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autofocus>
            </div>
            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" name="remember" value="1">
                <span class="text-sm text-ink-secondary">Remember me</span>
            </label>
            <button type="submit" class="ui-btn-primary w-full py-3">Verify &amp; sign in</button>
        </form>

        <form method="POST" action="{{ route('login.otp.resend') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $otpEmail }}">
            <button type="submit" class="ui-btn-outline w-full py-2.5 text-sm">Resend code</button>
        </form>

        <form method="POST" action="{{ route('login.otp.send') }}" id="form-change-email">
            @csrf
            <input type="hidden" name="email" value="">
            <button type="button" id="btn-change-email" class="w-full text-center text-sm text-accent hover:underline">Use a different email</button>
        </form>
    </div>

    <script>
        const tabPassword = document.getElementById('tab-password');
        const tabOtp = document.getElementById('tab-otp');
        const formPassword = document.getElementById('form-password');
        const formOtpSend = document.getElementById('form-otp-send');
        const formOtpVerify = document.getElementById('form-otp-verify');

        function showPasswordTab() {
            formPassword?.classList.remove('hidden');
            formOtpSend?.classList.add('hidden');
            formOtpVerify?.classList.add('hidden');
            tabPassword.className = 'flex-1 rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white';
            tabOtp.className = 'flex-1 rounded-lg px-3 py-2 text-sm font-semibold text-ink-secondary';
        }

        function showOtpTab(verify = false) {
            formPassword?.classList.add('hidden');
            tabOtp.className = 'flex-1 rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white';
            tabPassword.className = 'flex-1 rounded-lg px-3 py-2 text-sm font-semibold text-ink-secondary';
            if (verify) {
                formOtpSend?.classList.add('hidden');
                formOtpVerify?.classList.remove('hidden');
            } else {
                formOtpSend?.classList.remove('hidden');
                formOtpVerify?.classList.add('hidden');
            }
        }

        tabPassword?.addEventListener('click', showPasswordTab);
        tabOtp?.addEventListener('click', () => showOtpTab({{ $showOtpVerify ? 'true' : 'false' }}));

        document.getElementById('btn-change-email')?.addEventListener('click', () => {
            formOtpVerify?.classList.add('hidden');
            formOtpSend?.classList.remove('hidden');
            document.getElementById('otp_email')?.focus();
        });

        @if ($showOtpVerify)
            showOtpTab(true);
        @elseif ($showOtpSend)
            showOtpTab(false);
        @else
            showPasswordTab();
        @endif
    </script>

    <p class="mt-6 text-center text-xs text-ink-muted">
        <a href="{{ route('register') }}" class="text-accent hover:underline">Buy Semrush</a> ·
        <a href="{{ route('register') }}" class="text-accent hover:underline">Buy Ahrefs</a> ·
        <a href="{{ url('/#plans') }}" class="text-accent hover:underline">View Plans</a>
    </p>
@endsection

@section('seo_content')
    <div class="ui-card p-8 lg:sticky lg:top-8">
        <h2 class="text-xl font-bold text-ink">Login to Access Your Semrush &amp; Ahrefs Tools</h2>
        <p class="mt-4 text-sm leading-relaxed text-ink-secondary">
            Use your <strong>Semrushtoolz login</strong> to access your
            <strong>Semrush group buy</strong> and <strong>Ahrefs group buy</strong> subscription.
            Sign in with password or a one-time email code.
        </p>

        <h3 class="mt-6 font-semibold text-ink">What you get after login:</h3>
        <ul class="mt-3 space-y-2 text-sm text-ink-secondary">
            <li class="flex gap-2"><span class="text-success">✓</span> One-click Semrush cloud access</li>
            <li class="flex gap-2"><span class="text-success">✓</span> One-click Ahrefs cloud access</li>
            <li class="flex gap-2"><span class="text-success">✓</span> Manage your group buy subscription</li>
        </ul>

        <h3 class="mt-6 font-semibold text-ink">New to Semrushtoolz?</h3>
        <p class="mt-2 text-sm leading-relaxed text-ink-secondary">
            <a href="{{ route('register') }}" class="font-medium text-accent hover:underline">Create a free account</a>
            — verify your email, then choose a plan.
        </p>
    </div>
@endsection

@push('schema')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Semrush Login & Ahrefs Login — Semrushtoolz',
    'description' => 'Login to Semrushtoolz dashboard for Semrush group buy and Ahrefs group buy access.',
    'url' => route('login'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('footer_link')
    Don't have an account? <a href="{{ route('register') }}" class="font-medium text-accent hover:underline">Create one — buy Semrush &amp; Ahrefs cheap</a>
@endsection
