<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PayPal payment error</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; padding: 0 1rem; color: #111; }
        .box { border: 1px solid #fca5a5; background: #fef2f2; border-radius: 12px; padding: 1.25rem; }
        a { color: #ea580c; }
        code { font-size: 0.85em; word-break: break-word; }
    </style>
</head>
<body>
    <h1>PayPal payment could not open</h1>
    <div class="box">
        <p><strong>Error:</strong></p>
        <p><code>{{ $message }}</code></p>
        @if ($orderId)
            <p style="margin-top:1rem;font-size:0.9rem;opacity:0.8">Order #{{ $orderId }}</p>
        @endif
    </div>
    <p style="margin-top:1.5rem">
        <a href="{{ url('/dashboard/orders') }}">← Back to orders</a>
        ·
        <a href="{{ url('/dashboard/shop') }}">Try shop again</a>
    </p>
</body>
</html>
