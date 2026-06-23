<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\MailPanel\MailPanelClient;
use App\Services\MailPanel\MailPanelService;
use App\Services\MailPanel\MailPanelTemplateSyncService;
use App\Support\MailPanelSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailSettingsController extends Controller
{
    public function edit(MailPanelTemplateSyncService $syncService)
    {
        $config = SiteSetting::mailPanelConfig();

        return view('admin.email-settings.edit', [
            'config' => $config,
            'hasApiKey' => SiteSetting::hasMailPanelApiKey(),
            'mailReady' => MailPanelSettings::isConfigured(),
            'remoteTemplates' => $syncService->remoteTemplates(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'url' => ['required', 'url', 'max:500'],
            'api_key' => [
                Rule::requiredIf(fn () => $request->boolean('enabled') && ! SiteSetting::hasMailPanelApiKey()),
                'nullable',
                'string',
                'max:120',
            ],
        ], [
            'api_key.required' => 'Mail Panel API key required — email.sagartiwari.net admin se mk_... copy karo.',
        ]);

        SiteSetting::set('mail_panel_enabled', $request->boolean('enabled') ? '1' : '0');
        SiteSetting::set('mail_panel_url', rtrim($data['url'], '/'));

        if (filled($data['api_key'] ?? null)) {
            SiteSetting::set('mail_panel_api_key', trim($data['api_key']));
        }

        return back()->with('success', 'Email setup saved.');
    }

    public function testConnection(MailPanelClient $client)
    {
        if (! MailPanelSettings::isConfigured()) {
            return back()->with('error', 'Mail Panel URL aur API key save karo pehle.');
        }

        try {
            $stats = $client->todayStats();

            return back()->with('success', 'Connection OK — sent today: '.($stats['sent_today'] ?? 0).' / daily cap: '.($stats['daily_cap'] ?? '?'));
        } catch (\Throwable $exception) {
            return back()->with('error', 'Connection failed: '.$exception->getMessage());
        }
    }

    public function syncAll(MailPanelTemplateSyncService $syncService)
    {
        if (! MailPanelSettings::isConfigured()) {
            return back()->with('error', 'Mail Panel configure karo pehle.');
        }

        $results = $syncService->syncAllEnabled();

        if ($results['synced'] === 0 && ! empty($results['failed'])) {
            return back()->with('error', 'Sync failed: '.implode('; ', $results['failed']));
        }

        $message = $results['synced'].' preset(s) synced to Mail Panel.';

        if (! empty($results['failed'])) {
            $message .= ' Failed: '.implode('; ', array_map(
                fn ($key, $error) => $key.': '.$error,
                array_keys($results['failed']),
                $results['failed'],
            ));
        }

        return back()->with('success', $message);
    }

    public function testSend(Request $request, MailPanelService $mailPanel)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        if (! $mailPanel->isEnabled()) {
            return back()->with('error', 'Mail Panel enabled nahi hai.');
        }

        try {
            $result = $mailPanel->sendByPreset(
                \App\Models\EmailPreset::KEY_LOGIN_OTP,
                $data['test_email'],
                [
                    'name' => 'Test User',
                    'otp' => '123456',
                    'minutes' => 10,
                    'intro' => 'This is a test email from Semrushtoolz admin.',
                ],
            );

            return back()->with('success', 'Test email queued. Message ID: '.($result['message_id'] ?? 'n/a'));
        } catch (\Throwable $exception) {
            return back()->with('error', 'Test send failed: '.$exception->getMessage());
        }
    }
}
