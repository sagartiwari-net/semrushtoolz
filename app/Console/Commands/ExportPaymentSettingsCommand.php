<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportPaymentSettingsCommand extends Command
{
    protected $signature = 'payment:export-settings {--path=storage/app/private/payment-settings.json}';

    protected $description = 'Export UPI + PayPal site settings to a JSON file (for server import)';

    protected array $keys = [
        'buyahref_enabled',
        'buyahref_display_name',
        'buyahref_display_description',
        'buyahref_hub_url',
        'buyahref_hub_internal_url',
        'buyahref_api_key',
        'buyahref_api_secret',
        'buyahref_order_expiry_minutes',
        'paypal_enabled',
        'paypal_client_id',
        'paypal_client_secret',
        'paypal_webhook_id',
        'paypal_mode',
        'paypal_product_id',
    ];

    public function handle(): int
    {
        $path = base_path($this->option('path'));
        $dir = dirname($path);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $data = [];
        foreach ($this->keys as $key) {
            $value = SiteSetting::get($key);
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Exported '.count($data).' payment settings to: '.$path);
        $this->line('Copy this file to the server, then run: php artisan payment:import-settings');

        return self::SUCCESS;
    }
}
