@extends('layouts.guest')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Verify your email</h1>
    <p class="mt-2 text-sm text-ink-secondary">We sent a confirmation link to your inbox.</p>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="mt-8 rounded-xl border border-line bg-surface p-5 text-sm text-ink-secondary">
        @if ($email)
            <p>Email sent to: <strong class="text-ink">{{ $email }}</strong></p>
        @endif
        <p class="mt-3">Click the link in the email to activate your account. After verification you can sign in.</p>
    </div>

    <x-email-spam-tip class="mt-4" />

    <form class="mt-6" method="POST" action="{{ route('verification.send') }}">
        @csrf
        @if ($email)
            <input type="hidden" name="email" value="{{ $email }}">
        @else
            <div class="mb-4">
                <label class="ui-label" for="email">Your email</label>
                <input class="ui-input" type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>
        @endif
        <button type="submit" class="ui-btn-outline w-full py-3">Resend verification email</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" class="text-accent hover:underline">Back to sign in</a>
    </p>
@endsection

@section('footer_link')
    Already verified? <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Sign in</a>
@endsection
