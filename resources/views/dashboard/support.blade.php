@extends('layouts.dashboard')

@section('title', 'Support')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h1 class="dash-page-title !mb-0">Support</h1>
        <a href="{{ route('dashboard.support.create') }}" class="ui-btn-primary">Create Ticket</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="dash-card lg:col-span-2">
            <h3 class="dash-card-title">My Tickets</h3>
            <div class="space-y-3">
                @forelse ($tickets as $ticket)
                    <a href="{{ route('dashboard.support.show', $ticket['ticket_id']) }}" class="flex items-center justify-between rounded-xl border border-line px-4 py-3 transition hover:border-accent/30">
                        <div>
                            <div class="text-sm font-semibold text-ink">{{ $ticket['subject'] }}</div>
                            <div class="text-xs text-ink-muted">{{ $ticket['id'] }} · {{ $ticket['date'] }}</div>
                        </div>
                        <span @class([
                            'dash-badge-pending' => $ticket['status'] === 'Open',
                            'dash-badge-online' => $ticket['status'] === 'Replied',
                            'dash-badge-offline' => $ticket['status'] === 'Closed',
                        ])>{{ $ticket['status'] }}</span>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-ink-muted">No tickets yet. Create one if you need help.</p>
                @endforelse
            </div>
        </div>

        <div class="dash-card">
            <h3 class="dash-card-title">Contact</h3>
            <ul class="space-y-3 text-sm text-ink-secondary">
                @if ($contact['whatsapp'])
                    <li>
                        📱 WhatsApp:
                        <a
                            href="{{ \App\Models\SiteSetting::whatsappLink(url()->current()) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-medium text-accent hover:underline"
                        >{{ $contact['whatsapp'] }}</a>
                    </li>
                @endif
                @if ($contact['telegram'])
                    <li>✈️ Telegram: {{ $contact['telegram'] }}</li>
                @endif
                <li>📧 Email: <a href="mailto:{{ $contact['email'] }}" class="text-accent hover:underline">{{ $contact['email'] }}</a></li>
            </ul>
            <h3 class="dash-card-title mt-6">FAQ</h3>
            <div class="space-y-2">
                @foreach ($contact['faqs'] as $faq)
                    <details class="rounded-xl border border-line px-3 py-2 text-sm">
                        <summary class="cursor-pointer font-medium text-ink">{{ $faq['q'] }}</summary>
                        <p class="mt-2 text-xs text-ink-muted">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
@endsection
