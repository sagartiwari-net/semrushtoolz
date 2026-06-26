@extends('layouts.guest')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Forgot password</h1>
    <p class="mt-2 text-sm text-ink-secondary">Enter your email and we'll send a reset link.</p>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        <x-email-spam-tip class="mt-3" />
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    <form class="mt-8 space-y-5" action="{{ route('password.email') }}" method="POST">
        @csrf
        <div>
            <label class="ui-label" for="email">Email address</label>
            <input class="ui-input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <button type="submit" class="ui-btn-primary w-full py-3">Send reset link</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" class="text-accent hover:underline">Back to sign in</a>
    </p>
@endsection

@section('footer_link')
    Remember your password? <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Sign in</a>
@endsection
