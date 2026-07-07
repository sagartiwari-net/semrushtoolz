<?php

namespace App\Services;

use App\Models\ToolSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToolHandshakeService
{
    public function __construct(
        protected SubscriptionService $subscriptions
    ) {}

    /**
     * Same handshake as aMember route_tool.php:
     * POST https://{domain}/api/auth-handshake
     * payload: username, product_ids, client_ip, timestamp, signature
     */
    public function handshake(User $user, string $toolSlug, string $clientIp): string
    {
        $endpoint = app(ToolEndpointService::class)->endpoint($toolSlug);

        if (! $endpoint) {
            throw new \InvalidArgumentException("Unsupported tool: {$toolSlug}");
        }

        $group = $endpoint['group'] ?? null;
        if ($group && ! app(ToolAccessService::class)->userGrantsTool($user, $group)) {
            throw new \RuntimeException('Your plan does not include access to this tool.');
        }

        $username = $this->resolveUsername($user);
        $timestamp = time();
        $secretKey = $endpoint['secret_key'];
        $signature = hash_hmac('sha256', $username.':'.$timestamp, $secretKey);
        $productIds = $this->resolveProductIds($user);

        $payload = [
            'username' => $username,
            'product_ids' => $productIds,
            'client_ip' => $clientIp,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $handshakeUrl = "https://{$endpoint['domain']}/api/auth-handshake";

        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->acceptJson()
            ->post($handshakeUrl, $payload);

        if (! $response->successful()) {
            $message = $response->json('message') ?? strip_tags(substr($response->body(), 0, 300));
            Log::warning('Tool handshake failed', [
                'tool' => $toolSlug,
                'url' => $handshakeUrl,
                'status' => $response->status(),
                'message' => $message,
            ]);
            throw new \RuntimeException($this->friendlyHandshakeError($response->status(), $message, $endpoint['domain'] ?? ''));
        }

        $redirectUrl = $response->json('redirect_url');

        if (empty($redirectUrl)) {
            throw new \RuntimeException('Handshake succeeded but redirect_url is missing.');
        }

        $this->logSession($user, $toolSlug, $group, $clientIp, $redirectUrl, $endpoint['website_id'] ?? null);

        return $redirectUrl;
    }

    public function resolveUsername(User $user): string
    {
        $field = config('tool_endpoints.username_field', 'email');

        return (string) ($user->{$field} ?? $user->email);
    }

    public function hubConfig(string $group): ?array
    {
        return app(ToolEndpointService::class)->hubConfig($group);
    }

    public function resolveProductIds(User $user): array
    {
        return app(ToolEndpointService::class)->resolveProductIds($user);
    }

    protected function logSession(User $user, string $slug, ?string $group, string $ip, string $url, ?int $websiteId): void
    {
        ToolSession::create([
            'user_id' => $user->id,
            'tool' => $group ?? $slug,
            'proxy_session_id' => $slug,
            'status' => 'active',
            'ip_address' => $ip,
            'access_url' => $url,
            'started_at' => now(),
        ]);
    }

    protected function friendlyHandshakeError(int $status, string $message, string $domain): string
    {
        $base = "Handshake failed (HTTP {$status}): {$message}";

        if ($status === 502) {
            return $base.' — This proxy server appears offline. Try another access button or contact support.';
        }

        if ($status === 403 && str_contains($message, 'Invalid signature')) {
            return $base.' — Server secret/domain mismatch. Try another access button (e.g. Access 1).';
        }

        if ($status === 503) {
            return $base.' — Proxy database not connected. Try again later or use another server.';
        }

        return $base;
    }
}
