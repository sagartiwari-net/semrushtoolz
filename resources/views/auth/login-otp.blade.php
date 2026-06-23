@extends('layouts.guest')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Sign in with email code</h1>
    <p class="mt-2 text-sm text-ink-secondary">No password needed — we'll email you a one-time code.</p>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    @if ($email)
        <form class="mt-8 space-y-5" action="{{ route('login.otp.verify') }}" method="POST">
            @csrf
            <div>
                <label class="ui-label" for="email">Email</label>
                <input class="ui-input bg-surface" type="email" id="email" name="email" value="{{ $email }}" readonly>
            </div>
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

        <form class="mt-4" method="POST" action="{{ route('login.otp.resend') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="ui-btn-outline w-full py-2.5 text-sm">Resend code</button>
        </form>
    @else
        <form class="mt-8 space-y-5" action="{{ route('login.otp.send') }}" method="POST">
            @csrf
            <div>
                <label class="ui-label" for="email">Email address</label>
                <input class="ui-input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button type="submit" class="ui-btn-primary w-full py-3">Send login code</button>
        </form>
    @endif

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" class="text-accent hover:underline">Sign in with password instead</a>
    </p>
@endsection

@section('footer_link')
    <a href="{{ route('register') }}" class="font-medium text-accent hover:underline">Create an account</a>
@endsection
