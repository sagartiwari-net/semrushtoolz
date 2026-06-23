<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoProxyClient
{
    public function isConfigured(): bool
    {
        return filled(config('tools.proxy.base_url'));
    }

    public function health(): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'unconfigured', 'online' => false];
        }

        try {
            $response = $this->client()->get('/health');

            return [
                'status' => $response->successful() ? 'online' : 'error',
                'online' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Go proxy health check failed', ['error' => $e->getMessage()]);

            return ['status' => 'offline', 'online' => false, 'error' => $e->getMessage()];
        }
    }

    public function requestAccess(int $userId, string $tool, string $planSlug): array
    {
        $demoUrl = config("tools.demo_urls.{$tool}");

        if (! $this->isConfigured()) {
            if ($demoUrl) {
                return [
                    'session_id' => 'demo-'.uniqid(),
                    'access_url' => $demoUrl,
                    'mode' => 'demo',
                ];
            }

            throw new \RuntimeException('Tool proxy is not configured yet. Contact admin.');
        }

        $response = $this->client()->post('/access/request', [
            'user_id' => $userId,
            'tool' => $tool,
            'plan' => $planSlug,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to start tool session.');
        }

        $data = $response->json();

        return [
            'session_id' => $data['session_id'] ?? $data['id'] ?? null,
            'access_url' => $data['access_url'] ?? $data['url'] ?? null,
            'mode' => 'proxy',
        ];
    }

    public function endSession(string $sessionId): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        try {
            return $this->client()->delete("/access/{$sessionId}")->successful();
        } catch (\Throwable $e) {
            Log::warning('Go proxy end session failed', ['session' => $sessionId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function seatInfo(string $tool): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get("/tools/{$tool}/seats");

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function client()
    {
        $client = Http::timeout(config('tools.proxy.timeout', 15))
            ->baseUrl(rtrim(config('tools.proxy.base_url'), '/'));

        if ($key = config('tools.proxy.api_key')) {
            $client = $client->withToken($key);
        }

        return $client;
    }
}
