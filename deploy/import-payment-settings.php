<?php

/**
 * One-off import: php deploy/import-payment-settings.php [path-to-json]
 * JSON from local: php artisan payment:export-settings
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SiteSetting;
use App\Services\PayPalService;

$path = $argv[1] ?? base_path('storage/app/private/payment-settings.json');

if (! is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$data = json_decode(file_get_contents($path), true);

if (! is_array($data)) {
    fwrite(STDERR, "Invalid JSON.\n");
    exit(1);
}

foreach ($data as $key => $value) {
    if (! is_string($key) || (! is_string($value) && ! is_numeric($value))) {
        continue;
    }
    SiteSetting::set($key, (string) $value);
}

PayPalService::clearTokenCache();

echo 'Imported '.count($data)." payment settings.\n";
echo 'UPI ready: '.(SiteSetting::hasBuyahrefSecret() ? 'yes' : 'no')."\n";
echo 'PayPal ready: '.(SiteSetting::hasPayPalSecret() ? 'yes' : 'no')."\n";
