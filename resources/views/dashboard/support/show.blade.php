@extends('layouts.dashboard')

@section('title', $ticket->ticket_number)

@section('content')
    <div class="mb-4">
        <a href="{{ route('dashboard.support') }}" class="text-sm text-accent hover:underline">← Back to support</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="dash-card mb-4">
        <h1 class="text-lg font-semibold text-ink">{{ $ticket->subject }}</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ $ticket->ticket_number }} · {{ $ticket->statusLabel() }}</p>
    </div>

    <div class="dash-card mb-4 space-y-4">
        @foreach ($ticket->messages as $message)
            <div @class(['rounded-xl border px-4 py-3', $message->is_staff ? 'border-accent/30 bg-accent/5' : 'border-line'])>
                <div class="mb-2 flex items-center justify-between text-xs text-ink-muted">
                    <span>{{ $message->is_staff ? 'Support Team' : 'You' }}</span>
                    <span>{{ $message->created_at->format('M d, g:i A') }}</span>
                </div>
                <p class="whitespace-pre-wrap text-sm text-ink-secondary">{{ $message->body }}</p>
            </div>
        @endforeach
    </div>

    @if ($ticket->status !== 'closed')
        <form method="POST" action="{{ route('dashboard.support.reply', $ticket) }}" class="dash-card space-y-4">
            @csrf
            <h3 class="dash-card-title">Add message</h3>
            <textarea class="ui-input min-h-[120px]" name="body" required></textarea>
            <button type="submit" class="ui-btn-primary">Send</button>
        </form>
    @endif
@endsection
