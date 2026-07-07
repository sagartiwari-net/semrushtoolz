<?php

namespace App\Console\Commands;

use App\Models\ToolAccessServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VerifyProxyHandshakesCommand extends Command
{
    protected $signature = 'tools:verify-handshakes {--slug= : Test a single server slug}';

    protected $description = 'Probe bonus/proxy tool handshake endpoints (admin diagnostic)';

    public function handle(): int
    {
        $secret = env('TOOL_SECRET_RECLOUD', 'toolsmandi_recloud_secret_xyz123');
        $username = 'handshake-probe@semrushtoolz.local';
        $timestamp = time();
        $signature = hash_hmac('sha256', $username.':'.$timestamp, $secret);

        $payload = [
            'username' => $username,
            'product_ids' => [1, 2, 3, 4, 5, 6, 7, 22, 29, 11],
            'client_ip' => '127.0.0.1',
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $query = ToolAccessServer::with('group.tool')
            ->where('type', 'proxy')
            ->where('is_active', true)
            ->orderBy('slug');

        if ($slug = $this->option('slug')) {
            $query->where('slug', $slug);
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->warn('No active proxy servers found.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($servers as $server) {
            $domain = str_replace('.lclkaccess.store', '.1clkaccess.store', trim((string) $server->domain));
            $url = "https://{$domain}/api/auth-handshake";
            $serverSecret = $server->secret_key ?: $secret;
            $sig = hash_hmac('sha256', $username.':'.$timestamp, $serverSecret);
            $body = array_merge($payload, ['signature' => $sig]);

            try {
                $response = Http::timeout(12)
                    ->withOptions(['verify' => false])
                    ->acceptJson()
                    ->post($url, $body);

                $status = $response->status();
                $msg = strip_tags(mb_substr((string) $response->body(), 0, 80));

                if ($response->successful()) {
                    $this->line("<info>OK</info>  {$server->slug} ({$domain}) HTTP {$status}");
                } else {
                    $failed++;
                    $hint = $this->hintFor($status, $msg);
                    $this->line("<error>FAIL</error> {$server->slug} ({$domain}) HTTP {$status} — {$msg}");
                    if ($hint) {
                        $this->line("       → {$hint}");
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->line("<error>FAIL</error> {$server->slug} ({$domain}) — ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Checked {$servers->count()} server(s); {$failed} failed.");

        if ($failed > 0) {
            $this->line('If signature errors on nx* tools: fix ToolsMandi domain (.lclkaccess → .1clkaccess) and restart proxy.');
            $this->line('If 502 errors: deploy/restart the go-proxy on 1clkaccess.store for that subdomain.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function hintFor(int $status, string $message): ?string
    {
        if ($status === 502) {
            return 'Proxy offline — run git pull + reset-server.sh on 1clkaccess.store';
        }

        if ($status === 403 && str_contains($message, 'Invalid signature')) {
            return 'ToolsMandi ahrefs_websites.domain must match proxy public_host (use .1clkaccess.store)';
        }

        if ($status === 503) {
            return 'Proxy MySQL not connected — check config.json mysql_* settings';
        }

        return null;
    }
}
