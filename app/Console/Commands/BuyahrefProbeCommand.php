<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Services\BuyahrefPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class BuyahrefProbeCommand extends Command
{
    protected $signature = 'buyahref:probe';

    protected $description = 'Diagnose Payment Hub connectivity from this server';

    public function handle(BuyahrefPaymentService $buyahref): int
    {
        $config = SiteSetting::buyahrefConfig();
        $hubUrl = rtrim($config['hub_url'], '/');

        $this->info('Payment Hub probe from: '.gethostname());
        $this->line('Hub URL (public): '.$hubUrl);
        $this->line('Internal URL: '.($config['hub_internal_url'] ?: '— (not set)'));
        $this->newLine();

        foreach (['/health', '/api/v1/orders/__probe__/verify'] as $path) {
            $url = $hubUrl.$path;
            $this->line('GET '.$url);

            try {
                $response = Http::timeout(15)
                    ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'SemrushToolz-Probe/1.0'])
                    ->get($url);

                $body = mb_substr(trim((string) $response->body()), 0, 120);
                $this->line('  HTTP '.$response->status().' | '.str_replace(["\n", "\r"], ' ', $body));
            } catch (\Throwable $e) {
                $this->error('  Failed: '.$e->getMessage());
            }
        }

        $this->newLine();
        $result = $buyahref->testConnection();
        $this->line('Auth test: '.($result['ok'] ? 'OK' : 'FAIL').' — '.$result['message']);

        if (! $result['ok'] && str_contains($result['message'], 'invalid response')) {
            $this->newLine();
            $this->warn('This server is getting HTML instead of JSON from buyahref.com.');
            $this->line('Fix on buyahref.com server: ensure /payment/ proxies to Payment Hub (port 8090).');
            $this->line('SemrushToolz and Payment Hub are on different servers — do NOT use 127.0.0.1 internal URL here.');
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
