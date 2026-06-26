@extends('layouts.dashboard')

@section('title', 'Offline Payment')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.orders.show', $order) }}" class="text-sm text-ink-muted hover:text-accent">&larr; Order {{ $order->order_number }}</a>
        <h1 class="dash-page-title mt-2">Offline Payment</h1>
    </div>

    @if ($order->status === 'completed')
        <div class="dash-card text-center">
            <div class="text-4xl">✅</div>
            <h2 class="mt-3 text-xl font-bold text-success">Payment Approved!</h2>
            <a href="{{ route('dashboard.tools') }}" class="ui-btn-primary mt-5">Access My Tools</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="dash-card space-y-5">
                <div class="rounded-xl bg-surface p-5">
                    <h3 class="font-bold text-ink">Payment Details</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-muted">Amount to pay</dt><dd class="text-xl font-bold text-accent">{{ $formattedTotal }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-muted">Order reference</dt><dd class="font-mono font-medium">{{ $order->order_number }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-muted">Item</dt><dd>{{ $order->purchasedItemName() }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-line p-4 text-sm text-ink-secondary">
                    <p>{{ $offline['instructions'] }}</p>
                    @if ($whatsappDigits)
                        <a
                            href="https://wa.me/{{ $whatsappDigits }}?text={{ urlencode("Hi, I paid for order {$order->order_number}. Amount: {$formattedTotal}") }}"
                            target="_blank"
                            rel="noopener"
                            class="ui-btn-primary mt-4 w-full justify-center"
                        >
                            Contact on WhatsApp
                        </a>
                    @endif
                </div>
            </div>

            <div class="dash-card">
                <h3 class="font-bold text-ink">Upload Payment Proof</h3>
                <p class="mt-1 text-sm text-ink-muted">Upload a screenshot of your payment. Admin will verify within 24 hours.</p>

                @if (in_array($order->status, ['verifying']))
                    <div class="mt-4 rounded-xl border border-accent/30 bg-accent/10 px-4 py-3 text-sm text-accent">
                        Proof uploaded. Status: <strong>Verifying</strong>
                    </div>
                @endif

                @if ($order->payment_proof)
                    <a href="{{ Storage::url($order->payment_proof) }}" target="_blank" class="mt-4 block overflow-hidden rounded-xl border border-line">
                        <img src="{{ Storage::url($order->payment_proof) }}" alt="Uploaded proof" class="w-full">
                    </a>
                @endif

                @if (in_array($order->status, ['awaiting_proof', 'rejected']))
                    <form method="POST" action="{{ route('dashboard.orders.proof', $order) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="ui-label">Payment message</label>
                            <textarea
                                name="payment_note"
                                class="ui-input"
                                rows="4"
                                placeholder="e.g. Paid via Binance — TXN ID ABC123. Order ref: {{ $order->order_number }}"
                            >{{ old('payment_note', $order->payment_note) }}</textarea>
                            <p class="mt-1 text-xs text-ink-muted">Transaction ID, UTR, Binance ID, or any note for admin verification.</p>
                        </div>
                        <div>
                            <label class="ui-label">Screenshot (JPG/PNG, max 5MB)</label>
                            <input type="file" name="payment_proof" accept="image/*" class="ui-input" required>
                        </div>
                        <button type="submit" class="ui-btn-primary w-full">Upload Proof</button>
                    </form>
                @elseif ($order->payment_note)
                    <div class="mt-4 rounded-xl border border-line bg-surface px-4 py-3 text-sm">
                        <p class="font-semibold text-ink">Your payment message</p>
                        <p class="mt-2 whitespace-pre-wrap text-ink-secondary">{{ $order->payment_note }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
