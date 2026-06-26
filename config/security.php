<?php

return [
    // Unique IPs within 1 hour before user warning (requires multiple devices — see min_devices_for_warning).
    'max_ips_warning_hour' => (int) env('SECURITY_MAX_IPS_WARNING_HOUR', 3),

    // Unique IPs within 1 hour before auto-block (requires multiple devices).
    'max_ips_block_hour' => (int) env('SECURITY_MAX_IPS_BLOCK_HOUR', 5),

    // Minimum distinct device fingerprints needed to treat multi-IP as sharing (not mobile network hops).
    'min_devices_for_warning' => (int) env('SECURITY_MIN_DEVICES_WARNING', 2),
    'min_devices_for_block' => (int) env('SECURITY_MIN_DEVICES_BLOCK', 2),

    // Legacy daily/hourly thresholds (admin alerts only).
    'max_ips_per_day' => (int) env('SECURITY_MAX_IPS_PER_DAY', 8),
    'max_ips_per_hour' => (int) env('SECURITY_MAX_IPS_PER_HOUR', 6),
    'alert_cooldown_minutes' => (int) env('SECURITY_ALERT_COOLDOWN', 60),

    'auto_block_on_sharing' => filter_var(env('SECURITY_AUTO_BLOCK', true), FILTER_VALIDATE_BOOLEAN),

    // Throttle activity logs so every page view does not hit the DB.
    'activity_log_interval_minutes' => (int) env('SECURITY_ACTIVITY_LOG_INTERVAL', 5),

    // Admins are never auto-blocked by this system.
    'allow_concurrent_sessions' => filter_var(env('SECURITY_ALLOW_CONCURRENT', false), FILTER_VALIDATE_BOOLEAN),

    'purge_unverified_days' => (int) env('SECURITY_PURGE_UNVERIFIED_DAYS', 7),

    'purge_notify_email' => env('ADMIN_NOTIFY_EMAIL', env('MAIL_FROM_ADDRESS')),

    'exempt_admin_roles' => ['admin', 'super_admin'],

    'alert_types' => [
        'multiple_ip_daily' => 'Multiple IPs in 24 hours',
        'multiple_ip_hourly' => 'Multiple IPs in 1 hour',
        'concurrent_locations' => 'Concurrent access from different locations',
        'account_sharing' => 'Suspected account sharing',
        'ip_sharing_warning' => 'Account sharing warning (multi-IP + multi-device)',
        'ip_sharing_blocked' => 'Auto-blocked for account sharing',
        'device_mismatch' => 'Session used from a different device',
    ],
];
