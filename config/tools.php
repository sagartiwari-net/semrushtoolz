<?php

return [
    'proxy' => [
        'base_url' => env('GO_PROXY_URL'),
        'api_key' => env('GO_PROXY_API_KEY'),
        'timeout' => (int) env('GO_PROXY_TIMEOUT', 15),
    ],

    'session_max_minutes' => (int) env('TOOL_SESSION_MAX_MINUTES', 120),

    'demo_urls' => [
        'semrush' => env('TOOL_DEMO_URL_SEMRUSH'),
        'ahrefs' => env('TOOL_DEMO_URL_AHREFS'),
    ],

    'tools' => [
        'semrush' => [
            'name' => 'Semrush',
            'grant' => 'semrush',
        ],
        'ahrefs' => [
            'name' => 'Ahrefs',
            'grant' => 'ahrefs',
        ],
        'ahrefs_bar' => [
            'name' => 'Ahrefs Bar',
            'grant' => 'ahrefs_bar',
            'is_extension' => true,
        ],
    ],
];
