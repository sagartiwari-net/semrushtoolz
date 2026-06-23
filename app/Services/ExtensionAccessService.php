<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtensionAccessService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected ToolAccessService $toolAccess
    ) {}

    public function initiate(User $user, string $toolKey, string $clientIp): string
    {
        $config = SiteSetting::extensionConfig();

        if (empty($config['api_url']) || empty($config['secret_key'])) {
            throw new \RuntimeException('Extension server is not configured. Contact support.');
        }

        if (! $this->toolAccess->userHasActiveSubscription($user)) {
            throw new \RuntimeException('Active subscription required for extension access.');
        }

        $planIds = $this->subscriptions->activeSubscriptions($user)
            ->whereNotNull('plan_id')
            ->pluck('plan_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $payload = [
            'secret_key' => $config['secret_key'],
            'tool' => $toolKey,
            'username' => $user->email,
            'email' => $user->email,
            'plan_ids' => implode(',', $planIds),
            'client_slug' => $config['client_slug'],
            'website_url' => url('/'),
            'client_ip' => $clientIp,
        ];

        $apiUrl = rtrim($config['api_url'], '/').'/session/initiate';

        $response = Http::timeout(15)
            ->acceptJson()
            ->post($apiUrl, $payload);

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Extension access denied.';
            Log::warning('Extension access failed', ['tool' => $toolKey, 'status' => $response->status(), 'message' => $message]);
            throw new \RuntimeException($message);
        }

        $data = $response->json();

        if (empty($data['success']) || empty($data['token'])) {
            throw new \RuntimeException($data['message'] ?? 'Extension access denied.');
        }

        $launchBase = rtrim(str_replace('/api', '', $config['api_url']), '/');
        $client = urlencode($config['client_slug'] ?? '');

        return $launchBase.'/launch?token='.urlencode($data['token']).'&client='.$client;
    }

    public function userCanDownloadExtension(User $user): bool
    {
        if (! $this->toolAccess->userHasActiveSubscription($user)) {
            return false;
        }

        $visibility = SiteSetting::get('extension_visibility', 'any_subscription');

        if ($visibility === 'any_subscription') {
            return true;
        }

        $grantedSlugs = $this->subscriptions->grantedToolSlugsForUser($user);

        return Tool::where('is_active', true)
            ->where('access_type', 'extension')
            ->whereIn('slug', $grantedSlugs)
            ->exists();
    }

    public function extensionToolsForUser(User $user): array
    {
        $grantedSlugs = $this->subscriptions->grantedToolSlugsForUser($user);

        if ($grantedSlugs === []) {
            return [];
        }

        return Tool::where('is_active', true)
            ->where('access_type', 'extension')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Tool $tool) => in_array($tool->slug, $grantedSlugs))
            ->values()
            ->all();
    }
}
