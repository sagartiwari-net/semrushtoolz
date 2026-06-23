@extends('layouts.guest')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Reset password</h1>
    <p class="mt-2 text-sm text-ink-secondary">Choose a new password for your account.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    <form class="mt-8 space-y-5" action="{{ route('password.store') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="ui-label" for="email">Email</label>
            <input class="ui-input" type="email" id="email" name="email" value="{{ $email }}" required readonly>
        </div>
        <div>
            <label class="ui-label" for="password">New password</label>
            <input class="ui-input" type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div>
            <label class="ui-label" for="password_confirmation">Confirm password</label>
            <input class="ui-input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="ui-btn-primary w-full py-3">Reset password</button>
    </form>
@endsection

@section('footer_link')
    <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Back to sign in</a>
@endsection
