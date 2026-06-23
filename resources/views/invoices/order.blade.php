<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 32px; }
        h1 { font-size: 22px; margin: 0 0 4px; color: #111827; }
        .muted { color: #6b7280; }
        .header { margin-bottom: 28px; border-bottom: 2px solid #7c3aed; padding-bottom: 16px; }
        .grid { width: 100%; margin-bottom: 24px; }
        .grid td { vertical-align: top; width: 50%; padding: 0; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 10px 12px; text-align: left; }
        table.items th { background: #f9fafb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }
        .totals { width: 300px; margin-left: auto; }
        .totals td { padding: 6px 0; }
        .totals .grand { font-size: 16px; font-weight: bold; color: #7c3aed; border-top: 2px solid #e5e7eb; padding-top: 10px; }
        .footer { margin-top: 36px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $siteName }}</h1>
        <div class="muted">{{ $order->hasGst() ? 'Tax Invoice' : 'Tax Invoice / Receipt' }}</div>
        @if ($order->hasGst() && !empty($gst['number']))
            <div class="muted">GSTIN: {{ $gst['number'] }}</div>
        @endif
    </div>

    <table class="grid">
        <tr>
            <td>
                <strong>Bill to</strong><br>
                {{ $order->user->name }}<br>
                {{ $order->user->email }}
            </td>
            <td style="text-align: right;">
                <strong>Invoice #</strong> {{ $order->order_number }}<br>
                <strong>Date</strong> {{ ($order->paid_at ?? $order->created_at)->format('d M Y') }}<br>
                <strong>Status</strong> {{ $statusLabel }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Duration</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $order->purchasedItemName() }}</td>
                <td>{{ $order->duration_months }} month(s)</td>
                <td>{{ $currencySymbol }}{{ number_format($order->subtotal, $order->currency === 'usd' && $order->subtotal < 100 ? 2 : 0) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        @if ($order->discount > 0)
            <tr>
                <td class="muted">Duration discount</td>
                <td style="text-align: right;">-{{ $currencySymbol }}{{ number_format($order->discount, $order->currency === 'usd' && $order->discount < 100 ? 2 : 0) }}</td>
            </tr>
        @endif
        @if ($order->referral_bonus_discount > 0)
            <tr>
                <td class="muted">Referral bonus</td>
                <td style="text-align: right;">-{{ $currencySymbol }}{{ number_format($order->referral_bonus_discount, 0) }}</td>
            </tr>
        @endif
        @if ($order->coupon_discount > 0)
            <tr>
                <td class="muted">Coupon ({{ $order->coupon_code }})</td>
                <td style="text-align: right;">-{{ $currencySymbol }}{{ number_format($order->coupon_discount, 0) }}</td>
            </tr>
        @endif
        @if ($order->hasGst())
            <tr>
                <td class="muted">Taxable amount</td>
                <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($order->amountBeforeGst(), 2) }}</td>
            </tr>
            <tr>
                <td class="muted">{{ $gst['label'] }} ({{ rtrim(rtrim(number_format((float) $order->gst_rate, 2), '0'), '.') }}%)</td>
                <td style="text-align: right;">{{ $currencySymbol }}{{ number_format($order->gst_amount, 2) }}</td>
            </tr>
        @endif
        @if ((float) $order->wallet_cashback_amount > 0)
            <tr>
                <td class="muted">Wallet cashback awarded</td>
                <td style="text-align: right;" class="text-success">+₹{{ number_format($order->wallet_cashback_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="muted">Payment method</td>
            <td style="text-align: right;">{{ $order->payment_method === 'wallet' ? 'SemrushToolz Wallet' : ucfirst($order->payment_method ?? '—') }}</td>
        </tr>
        <tr class="grand">
            <td>Total paid</td>
            <td style="text-align: right;">{{ $formattedTotal }}</td>
        </tr>
    </table>

    @if ($order->paid_at)
        <p class="muted">Paid on {{ $order->paid_at->format('d M Y, H:i') }} ({{ strtoupper($order->currency) }})</p>
    @endif

    <div class="footer">
        Thank you for your purchase. For billing questions contact {{ $supportEmail }}.
        This document is generated electronically and is valid without a signature.
    </div>
</body>
</html>
