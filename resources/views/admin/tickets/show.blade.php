@extends('layouts.admin')

@section('title', $ticket->ticket_number)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.tickets') }}" class="text-sm text-accent hover:underline">← Back to tickets</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="dash-card mb-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-ink">{{ $ticket->subject }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ $ticket->ticket_number }} · {{ $ticket->user->name }} ({{ $ticket->user->email }})</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="dash-badge-pending">{{ $ticket->priorityLabel() }} priority</span>
                <span @class(['dash-badge-online' => $ticket->status === 'replied', 'dash-badge-pending' => $ticket->status === 'open', 'dash-badge-offline' => $ticket->status === 'closed'])>{{ $ticket->statusLabel() }}</span>
            </div>
        </div>
        @if ($ticket->status !== 'closed')
            <form method="POST" action="{{ route('admin.tickets.close', $ticket) }}" class="mt-4">
                @csrf
                <button type="submit" class="ui-btn-outline text-sm">Close ticket</button>
            </form>
        @endif
    </div>

    <div class="dash-card mb-4 space-y-4">
        @foreach ($ticket->messages as $message)
            <div @class(['rounded-xl border px-4 py-3', $message->is_staff ? 'border-accent/30 bg-accent/5' : 'border-line'])>
                <div class="mb-2 flex items-center justify-between text-xs text-ink-muted">
                    <span>{{ $message->is_staff ? 'Support Team' : ($message->user?->name ?? 'User') }}</span>
                    <span>{{ $message->created_at->format('M d, g:i A') }}</span>
                </div>
                <p class="whitespace-pre-wrap text-sm text-ink-secondary">{{ $message->body }}</p>
            </div>
        @endforeach
    </div>

    @if ($ticket->status !== 'closed')
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="dash-card space-y-4">
            @csrf
            <h3 class="dash-card-title">Reply</h3>
            <textarea class="ui-input min-h-[120px]" name="body" required placeholder="Type your reply…"></textarea>
            <button type="submit" class="ui-btn-primary">Send Reply</button>
        </form>
    @endif
@endsection
