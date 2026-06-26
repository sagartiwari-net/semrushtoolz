@extends('layouts.guest')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Security verification</h1>
    <p class="mt-2 text-sm text-ink-secondary">Enter the code we sent to <strong>{{ $email }}</strong></p>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <x-email-spam-tip class="mt-4" />

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    <div class="mt-4 rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-xs text-warning">
        For your security, we ask for email verification every {{ config('auth_otp.recheck_days', 15) }} days.
    </div>

    <form class="mt-6 space-y-5" action="{{ route('login.otp.challenge.verify') }}" method="POST">
        @csrf
        <div>
            <label class="ui-label" for="code">6-digit code</label>
            <input class="ui-input text-center font-mono text-lg tracking-widest" type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" required autofocus>
        </div>
        <button type="submit" class="ui-btn-primary w-full py-3">Verify &amp; continue</button>
    </form>

    <form class="mt-4" method="POST" action="{{ route('login.otp.challenge.resend') }}">
        @csrf
        <button type="submit" class="ui-btn-outline w-full py-2.5 text-sm">Resend code</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" class="text-accent hover:underline">Back to sign in</a>
    </p>
@endsection
