<?php

namespace App\Console\Commands;

use App\Services\EmailPolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncDisposableEmailDomainsCommand extends Command
{
    protected $signature = 'email-policy:sync-disposable {--force : Re-download even if recently synced}';

    protected $description = 'Download latest disposable email domain blocklist and refresh cache';

    /** @var array<int, string> */
    protected array $sources = [
        'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.txt',
        'https://raw.githubusercontent.com/7c/fakefilter/main/txt/data.txt',
    ];

    public function handle(): int
    {
        $path = 'blocklists/disposable_domains.txt';
        $disk = Storage::disk('local');

        if (! $this->option('force') && $disk->exists($path)) {
            $ageHours = now()->diffInHours($disk->lastModified($path));

            if ($ageHours < 24) {
                $this->info("Blocklist is fresh ({$ageHours}h old). Use --force to re-download.");

                return self::SUCCESS;
            }
        }

        $disk->makeDirectory('blocklists');
        $merged = [];

        foreach ($this->sources as $url) {
            $this->line("Fetching {$url}");

            try {
                $response = Http::timeout(30)->get($url);

                if (! $response->successful()) {
                    $this->warn("Failed: HTTP {$response->status()}");

                    continue;
                }

                $lines = preg_split('/\R+/', $response->body()) ?: [];

                foreach ($lines as $line) {
                    $domain = strtolower(trim($line));

                    if ($domain === '' || str_starts_with($domain, '#')) {
                        continue;
                    }

                    if (str_contains($domain, '@')) {
                        $domain = substr(strrchr($domain, '@'), 1);
                    }

                    $domain = ltrim($domain, '.');

                    if (filter_var('test@'.$domain, FILTER_VALIDATE_EMAIL)) {
                        $merged[$domain] = true;
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("Error fetching {$url}: {$e->getMessage()}");
            }
        }

        if ($merged === []) {
            $this->error('No domains downloaded. Keeping existing blocklist.');

            return self::FAILURE;
        }

        $domains = array_keys($merged);
        sort($domains);

        $disk->put($path, implode("\n", $domains)."\n");
        app(EmailPolicyService::class)->refreshBlocklistCache();

        $this->info('Saved '.count($domains).' disposable domains to storage/app/'.$path);

        return self::SUCCESS;
    }
}
