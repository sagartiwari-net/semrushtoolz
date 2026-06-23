<?php

return [
    'max_ips_per_day' => env('SECURITY_MAX_IPS_PER_DAY', 3),
    'max_ips_per_hour' => env('SECURITY_MAX_IPS_PER_HOUR', 2),
    'alert_cooldown_minutes' => env('SECURITY_ALERT_COOLDOWN', 60),
    'block_on_critical' => env('SECURITY_AUTO_BLOCK', false),

    'alert_types' => [
        'multiple_ip_daily' => 'Multiple IPs in 24 hours',
        'multiple_ip_hourly' => 'Multiple IPs in 1 hour',
        'concurrent_locations' => 'Concurrent access from different locations',
        'account_sharing' => 'Suspected account sharing',
    ],
];
