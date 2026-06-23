<?php

namespace App\Services\MailPanel;

use App\Exceptions\MailPanelException;
use App\Models\EmailPreset;
use App\Support\MailPanelSettings;
use Illuminate\Support\Collection;

class MailPanelTemplateSyncService
{
    public function __construct(
        private readonly MailPanelClient $client,
    ) {}

    public function syncPreset(EmailPreset $preset): void
    {
        if (! MailPanelSettings::isConfigured()) {
            throw new MailPanelException('Mail Panel is not configured.');
        }

        $this->client->syncTemplate([
            'slug' => $preset->slug,
            'name' => $preset->name,
            'subject' => $preset->subject,
            'html_body' => $preset->html_body,
            'text_body' => $preset->text_body,
            'type' => $preset->type,
        ]);

        $preset->update(['last_synced_at' => now()]);
    }

    public function syncAllEnabled(): array
    {
        $results = ['synced' => 0, 'failed' => []];

        EmailPreset::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->each(function (EmailPreset $preset) use (&$results) {
                try {
                    $this->syncPreset($preset);
                    $results['synced']++;
                } catch (MailPanelException $exception) {
                    $results['failed'][$preset->key] = $exception->getMessage();
                }
            });

        return $results;
    }

    public function remoteTemplates(): Collection
    {
        if (! MailPanelSettings::isConfigured()) {
            return collect();
        }

        try {
            $response = $this->client->listTemplates();

            return collect($response['templates'] ?? []);
        } catch (MailPanelException) {
            return collect();
        }
    }
}
