<?php

namespace App\Services\MailPanel;

use App\Exceptions\MailPanelException;
use App\Support\MailPanelSettings;
use Illuminate\Support\Facades\Http;

class MailPanelClient
{
    public function send(string $to, string $template, array $data = [], ?string $subject = null): array
    {
        $payload = [
            'to' => $to,
            'template' => $template,
            'data' => $data,
        ];

        if ($subject !== null) {
            $payload['subject'] = $subject;
        }

        return $this->request('POST', '/api/v1/send', $payload);
    }

    public function syncTemplate(array $payload): array
    {
        return $this->request('POST', '/api/v1/templates/sync', $payload);
    }

    public function listTemplates(): array
    {
        return $this->request('GET', '/api/v1/templates');
    }

    public function deleteTemplate(string $slug): array
    {
        return $this->request('DELETE', '/api/v1/templates/'.$slug);
    }

    public function status(string $messageId): array
    {
        return $this->request('GET', '/api/v1/status/'.$messageId);
    }

    public function todayStats(): array
    {
        return $this->request('GET', '/api/v1/stats/today');
    }

    public function getSettings(): array
    {
        return $this->request('GET', '/api/v1/settings');
    }

    public function updateDailyLimit(int $dailyLimit): array
    {
        return $this->request('PATCH', '/api/v1/settings', [
            'daily_limit' => $dailyLimit,
        ]);
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $config = MailPanelSettings::config();

        if (! filled($config['api_key']) || ! filled($config['url'])) {
            throw new MailPanelException('Mail Panel URL or API key is missing.');
        }

        $request = Http::baseUrl(rtrim($config['url'], '/'))
            ->withHeaders([
                'X-API-Key' => $config['api_key'],
                'Accept' => 'application/json',
            ])
            ->timeout($config['timeout']);

        $response = match (strtoupper($method)) {
            'GET' => $request->get($path),
            'PATCH' => $request->patch($path, $payload ?? []),
            'DELETE' => $request->delete($path),
            default => $request->post($path, $payload ?? []),
        };

        $body = $response->json();

        if (! is_array($body)) {
            throw new MailPanelException('Invalid response from Mail Panel API.', $response->status());
        }

        if ($response->failed() || ($body['success'] ?? true) === false) {
            throw new MailPanelException(
                (string) ($body['message'] ?? 'Mail Panel API request failed.'),
                $response->status(),
            );
        }

        return $body;
    }
}
