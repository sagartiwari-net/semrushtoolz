@extends('layouts.dashboard')

@section('title', 'Wallet Top-up')

@section('content')
    <div class="mb-5">
        <a href="{{ route('dashboard.wallet') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Back to Wallet</a>
        <h1 class="dash-page-title mt-2">Add Money to Wallet</h1>
        <p class="mt-1 text-sm text-ink-muted">Current balance: <strong>{{ $balanceLabel }}</strong></p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('dashboard.wallet.topup.store') }}" class="dash-card space-y-6">
            @csrf

            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-muted">Select Amount (INR)</h2>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($topupAmounts as $amount)
                        <label class="flex cursor-pointer items-center justify-center rounded-xl border border-line p-4 font-bold transition hover:border-accent/40 has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                            <input type="radio" name="amount" value="{{ $amount }}" class="sr-only" @checked(old('amount') == $amount) required>
                            ₹{{ number_format($amount) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-muted">Payment Method</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($paymentMethods as $method)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-4 transition hover:border-accent/40 has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ $method['id'] }}"
                                class="mt-1"
                                @checked(old('payment_method', $paymentMethods[0]['id'] ?? '') === $method['id'])
                                required
                            >
                            <div>
                                <div class="font-semibold text-ink">{{ $method['name'] }}</div>
                                <div class="text-sm text-ink-muted">{{ $method['desc'] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="ui-btn-primary w-full py-3">Continue to Payment</button>
        </form>

        <div class="dash-card space-y-3 text-sm text-ink-secondary">
            <h2 class="dash-card-title">Important</h2>
            <ul class="list-inside list-disc space-y-2">
                <li>Fixed top-up amounts only: ₹50, ₹100, ₹300, ₹500, ₹1000.</li>
                <li>Wallet balance can be used for subscription purchases in INR.</li>
                <li>No cashback is awarded on wallet top-ups.</li>
                <li>Get {{ (int) (\App\Models\SiteSetting::walletConfig()['purchase_cashback_percent'] * 100) }}% cashback when you pay via UPI, PayPal, or offline for subscriptions.</li>
            </ul>
        </div>
    </div>
@endsection
