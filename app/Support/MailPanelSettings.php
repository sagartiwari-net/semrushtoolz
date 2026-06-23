<?php

namespace App\Support;

use App\Models\SiteSetting;

class MailPanelSettings
{
    public static function config(): array
    {
        $enabled = SiteSetting::get('mail_panel_enabled');
        $url = SiteSetting::get('mail_panel_url');
        $apiKey = SiteSetting::get('mail_panel_api_key');

        return [
            'enabled' => filter_var(
                $enabled !== null ? $enabled : config('mail_panel.enabled', true),
                FILTER_VALIDATE_BOOLEAN,
            ),
            'url' => filled($url) ? $url : (string) config('mail_panel.url'),
            'api_key' => filled($apiKey) ? $apiKey : (string) config('mail_panel.api_key'),
            'timeout' => (int) config('mail_panel.timeout', 30),
        ];
    }

    public static function isConfigured(): bool
    {
        $config = static::config();

        return $config['enabled']
            && filled($config['url'])
            && filled($config['api_key']);
    }

    public static function hasApiKeyInDatabase(): bool
    {
        return filled(SiteSetting::get('mail_panel_api_key'));
    }
}
