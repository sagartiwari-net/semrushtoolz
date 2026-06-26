<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Admin alert email (fallback when Sentry is not configured)
  |--------------------------------------------------------------------------
  */

  'alert_email' => env('ADMIN_NOTIFY_EMAIL', env('MAIL_FROM_ADDRESS')),

  'alert_cooldown_minutes' => (int) env('MONITORING_ALERT_COOLDOWN', 30),

  /*
  |--------------------------------------------------------------------------
  | Uptime monitoring
  |--------------------------------------------------------------------------
  |
  | Point UptimeRobot (or similar) at this URL — Laravel ships /up by default.
  |
  */

  'health_url' => env('APP_URL', 'http://localhost').'/up',

];
