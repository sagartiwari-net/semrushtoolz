<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mail Panel API
    |--------------------------------------------------------------------------
    |
    | SemrushToolz sends all transactional mail through the central Mail Panel
    | (email.sagartiwari.net). The API key determines the sending domain
    | (e.g. contact@semrushtoolz.com).
    |
    */

    'enabled' => env('MAIL_PANEL_ENABLED', true),

    'url' => env('MAIL_PANEL_URL', 'https://email.sagartiwari.net'),

    'api_key' => env('MAIL_PANEL_API_KEY'),

    'timeout' => (int) env('MAIL_PANEL_TIMEOUT', 30),

    'templates' => [
        'login_otp' => env('MAIL_PANEL_TEMPLATE_LOGIN_OTP', 'semrushtoolz-login-otp'),
        'periodic_otp' => env('MAIL_PANEL_TEMPLATE_PERIODIC_OTP', 'semrushtoolz-periodic-otp'),
        'verify_email' => env('MAIL_PANEL_TEMPLATE_VERIFY_EMAIL', 'semrushtoolz-verify-email'),
        'reset_password' => env('MAIL_PANEL_TEMPLATE_RESET_PASSWORD', 'semrushtoolz-reset-password'),
    ],

];
