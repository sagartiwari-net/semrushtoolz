<?php

namespace App\Console\Commands;

use App\Models\EmailPreset;
use App\Models\SiteSetting;
use App\Services\BuyahrefPaymentService;
use App\Services\MailPanel\MailPanelService;
use App\Services\TurnstileService;
use App\Support\MailPanelSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IntegrationsStatusCommand extends Command
{
    protected $signature = 'integrations:status';

    protected $description = 'Check Mail Panel, UPI (Buyahref), Turnstile, and payment settings on this server';

    public function handle(
        MailPanelService $mailPanel,
        BuyahrefPaymentService $buyahref,
        TurnstileService $turnstile,
    ): int {
        $this->info('SemrushToolz integration status');
        $this->line('APP_URL: '.config('app.url'));
        $this->line('APP_ENV: '.config('app.env'));
        $this->newLine();

        $ok = true;

        $mailConfig = MailPanelSettings::config();
        $this->line('── Mail Panel (OTP + verification email) ──');
        $this->line('Enabled:  '.($mailConfig['enabled'] ? 'yes' : 'no'));
        $this->line('URL:      '.($mailConfig['url'] ?: '—'));
        $this->line('API key:  '.($mailConfig['api_key'] ? substr($mailConfig['api_key'], 0, 8).'...' : 'MISSING'));
        $this->line('Presets:  '.EmailPreset::query()->count().' in database');
        $mailResult = $mailPanel->testConnection();
        $this->line('Test:     '.($mailResult['ok'] ? '<info>OK</info>' : '<error>FAIL</error>').' — '.$mailResult['message']);
        $ok = $ok && $mailResult['ok'];
        $this->newLine();

        $buyahrefConfig = SiteSetting::buyahrefConfig();
        $this->line('── UPI / Buyahref Payment Hub ──');
        $this->line('Enabled:  '.($buyahrefConfig['enabled'] ? 'yes' : 'no'));
        $this->line('Hub URL:  '.($buyahrefConfig['hub_url'] ?: '—'));
        if (filled($buyahrefConfig['hub_internal_url'] ?? null)) {
            $this->line('Internal: '.($buyahrefConfig['hub_internal_url']).' (API calls use this)');
        }
        $this->line('API key:  '.($buyahrefConfig['api_key'] ? substr($buyahrefConfig['api_key'], 0, 8).'...' : 'MISSING'));
        $this->line('Secret:   '.(SiteSetting::hasBuyahrefSecret() ? 'saved' : 'MISSING'));
        $this->line('Webhook:  '.url('/webhooks/buyahref'));
        $buyahrefResult = $buyahref->testConnection();
        $this->line('Test:     '.($buyahrefResult['ok'] ? '<info>OK</info>' : '<error>FAIL</error>').' — '.$buyahrefResult['message']);
        $ok = $ok && $buyahrefResult['ok'];
        $this->newLine();

        $this->line('── Cloudflare Turnstile (register) ──');
        $this->line('Enabled:  '.($turnstile->isEnabled() ? 'yes' : 'no'));
        $this->newLine();

        $paymentJson = storage_path('app/private/payment-settings.json');
        $this->line('── Import helper ──');
        $this->line('payment-settings.json: '.(File::exists($paymentJson) ? 'found — run php artisan payment:import-settings' : 'not uploaded'));
        $this->newLine();

        if (! $ok) {
            $this->warn('Fix failing items above, then run: php artisan integrations:status');
            $this->line('Logs: grep ERROR storage/logs/laravel.log | tail -20');

            return self::FAILURE;
        }

        $this->info('All integrations look good on this server.');

        return self::SUCCESS;
    }
}
