<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Services\PayPalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportPaymentSettingsCommand extends Command
{
    protected $signature = 'payment:import-settings {--path=storage/app/private/payment-settings.json}';

    protected $description = 'Import UPI + PayPal site settings from JSON (exported from local)';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! File::exists($path)) {
            $this->error('File not found: '.$path);

            return self::FAILURE;
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            $this->error('Invalid JSON file.');

            return self::FAILURE;
        }

        foreach ($data as $key => $value) {
            if (! is_string($key) || (! is_string($value) && ! is_numeric($value))) {
                continue;
            }

            SiteSetting::set($key, (string) $value);
        }

        PayPalService::clearTokenCache();

        $this->info('Imported '.count($data).' payment settings.');
        $this->line('UPI ready: '.(SiteSetting::hasBuyahrefSecret() ? 'yes' : 'no'));
        $this->line('PayPal ready: '.(SiteSetting::hasPayPalSecret() ? 'yes' : 'no'));
        $this->newLine();
        $this->line('Update webhooks on external dashboards:');
        $this->line('  Buyahref: '.url('/webhooks/buyahref'));
        $this->line('  PayPal:   '.url('/webhooks/paypal'));

        return self::SUCCESS;
    }
}
