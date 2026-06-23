<?php

return [
    'order_expiry_minutes' => 30,

    'upi' => [
        'id' => env('UPI_ID', 'semrushtoolz@paytm'),
        'name' => env('UPI_NAME', 'Semrushtoolz'),
        'qr_image' => env('UPI_QR_IMAGE', null),
        'imap_host' => env('UPI_IMAP_HOST'),
        'imap_port' => env('UPI_IMAP_PORT', 993),
        'imap_username' => env('UPI_IMAP_USERNAME'),
        'imap_password' => env('UPI_IMAP_PASSWORD'),
    ],

    'offline' => [
        'whatsapp' => env('OFFLINE_WHATSAPP', '919876543210'),
        'telegram' => env('OFFLINE_TELEGRAM', null),
        'instructions' => 'Send payment via Binance, bank transfer, or WhatsApp. Include your order number in the payment note.',
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'product_id' => env('PAYPAL_PRODUCT_ID'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
    ],
];
