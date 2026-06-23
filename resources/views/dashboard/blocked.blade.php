<!DOCTYPE html>
<html lang="en" data-theme="semrushtoolz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />
    <title>Account Blocked — Semrushtoolz</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-canvas p-4">
    <div class="ui-card max-w-md p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-danger/10 text-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1 class="mt-6 text-2xl font-bold text-ink">Account Blocked</h1>
        <p class="mt-3 text-sm leading-relaxed text-ink-secondary">
            Your account has been suspended due to a security policy violation.
        </p>
        <div class="mt-4 rounded-xl bg-danger/5 p-4 text-left text-sm text-ink-secondary">
            <p><strong>Reason:</strong> {{ $reason }}</p>
            @if ($blocked_at)
                <p class="mt-2"><strong>Blocked on:</strong> {{ $blocked_at->format('M d, Y H:i') }}</p>
            @endif
        </div>
        <p class="mt-4 text-xs text-ink-muted">
            If you believe this is a mistake, contact support with your account email.
        </p>
        <div class="mt-6 flex flex-col gap-2">
            <a href="{{ route('dashboard.support') }}" class="ui-btn-primary">Contact Support</a>
            <a href="{{ route('home') }}" class="ui-btn-outline">Back to Home</a>
        </div>
    </div>
</body>
</html>
