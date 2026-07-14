@extends('layouts.reseller')

@section('title', 'Pay via UPI')

@section('content')
    <div class="mb-5">
        <a href="{{ route('reseller.balance.index') }}" class="text-sm text-accent hover:underline">← Balance</a>
        <h1 class="dash-page-title mt-2">Pay via UPI</h1>
        <p class="text-sm text-ink-secondary">Order {{ $order->order_number }} — credit ₹{{ number_format($creditInr, 2) }} after payment.</p>
    </div>

    @if ($order->status === 'completed')
        <div class="ui-card p-6 text-center">
            <p class="text-lg font-bold text-success">Payment completed</p>
            <a href="{{ route('reseller.balance.index') }}" class="ui-btn-primary mt-4 inline-flex">Back to balance</a>
        </div>
    @else
        <div class="ui-card max-w-lg space-y-4 p-5">
            <p class="text-sm text-ink-secondary">Amount to pay: <strong>{{ $formattedTotal }}</strong></p>
            <p class="text-sm text-ink-muted">UPI checkout could not open automatically. Ask admin to check Payment Integration, or try again.</p>
            <a href="{{ route('reseller.balance.pay.upi', $order) }}" class="ui-btn-primary inline-flex">Retry UPI checkout</a>
            @if ($order->expires_at)
                <p class="text-xs text-warning">Expires {{ $order->expires_at->format('M j, H:i') }} ({{ $expiryMinutes }} min window).</p>
            @endif
        </div>
    @endif
@endsection
