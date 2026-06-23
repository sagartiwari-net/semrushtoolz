<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Services\PayPalService;
use Illuminate\Console\Command;

class PayPalStatus extends Command
{
    protected $signature = 'paypal:status';

    protected $description = 'Check PayPal integration status and test API connection';

    public function handle(PayPalService $paypal): int
    {
        $c = SiteSetting::paypalConfig();

        $this->info('PayPal Integration Status');
        $this->line('─────────────────────────');
        $this->line('Enabled:     '.($c['enabled'] ? 'Yes' : 'No'));
        $this->line('Mode:        '.$c['mode']);
        $this->line('Client ID:   '.($c['client_id'] ? substr($c['client_id'], 0, 12).'...' : '—'));
        $this->line('Secret:      '.(SiteSetting::hasPayPalSecret() ? 'Saved' : 'Missing'));
        $this->line('Webhook ID:  '.($c['webhook_id'] ?: '— (optional for local)'));
        $this->line('Webhook URL: '.url('/webhooks/paypal'));
        $this->newLine();

        if (! $paypal->isConfigured()) {
            $this->warn('Not ready. Admin → Payment Integration → PayPal section configure karo.');

            return self::FAILURE;
        }

        $result = $paypal->testConnection();

        if ($result['ok']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
