@extends('layouts.guest')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Admin Login</h1>
    <p class="mt-2 text-sm text-ink-secondary">Semrushtoolz administration panel</p>

    @if (session('error'))
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="mt-8 space-y-5" action="{{ route('admin.login.submit') }}" method="POST">
        @csrf
        <div>
            <label class="ui-label" for="email">Admin email</label>
            <input class="ui-input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div>
            <label class="ui-label" for="password">Password</label>
            <input class="ui-input" type="password" id="password" name="password" required>
        </div>
        <label class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" name="remember">
            <span class="text-sm text-ink-secondary">Remember me</span>
        </label>
        <button type="submit" class="ui-btn-primary w-full py-3">Sign In to Admin</button>
    </form>
@endsection

@section('footer_link')
    <a href="{{ route('home') }}" class="text-accent hover:underline">← Back to website</a>
@endsection
