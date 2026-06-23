<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\ToolAccessGroup;
use App\Models\ToolAccessServer;
use App\Models\User;

class ToolEndpointService
{
    public function hubConfig(string $groupSlug): ?array
    {
        $group = ToolAccessGroup::with(['tool.activeCredentials', 'activeServers'])
            ->where('slug', $groupSlug)
            ->where('is_active', true)
            ->first();

        if (! $group) {
            return config("tool_endpoints.groups.{$groupSlug}");
        }

        $tool = $group->tool;
        $accessType = $tool?->access_type ?? 'cloud';

        $config = [
            'title' => $group->title,
            'subtitle' => $group->subtitle,
            'grant' => $tool?->slug ?? $group->grant,
            'logo' => $group->logo_url ?? $tool?->logo_url,
            'access_type' => $accessType,
            'whatsapp_number' => $tool?->whatsapp_number,
            'whatsapp_message' => $tool?->whatsapp_message ?? 'Contact us on WhatsApp for activation',
            'official_url' => $tool?->official_url,
            'extension_download_url' => $tool?->extension_download_url,
            'credentials' => $tool?->activeCredentials->map(fn ($c) => [
                'label' => $c->label,
                'username' => $c->username,
                'password' => $c->password,
                'official_url' => $c->official_url ?? $tool?->official_url,
            ])->values()->all() ?? [],
            'extension' => SiteSetting::extensionConfig(),
            'sections' => [],
        ];

        $servers = $group->activeServers;

        if ($servers->isEmpty() && $accessType === 'cloud') {
            $fallback = config("tool_endpoints.groups.{$groupSlug}");
            if ($fallback) {
                $config['sections'] = $fallback['sections'] ?? [];

                return $config;
            }
        }

        if ($servers->isNotEmpty()) {
            $config['sections'] = $servers
                ->groupBy(fn ($s) => $s->section_title ?? '')
                ->map(function ($items, $sectionTitle) {
                    return [
                        'title' => $sectionTitle !== '' ? $sectionTitle : null,
                        'buttons' => $items->map(fn ($s) => $this->buttonFromServer($s))->values()->all(),
                    ];
                })
                ->values()
                ->all();
        }

        return $config;
    }

    public function endpoint(string $slug): ?array
    {
        $server = ToolAccessServer::with('group.tool')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($server && $server->isProxy()) {
            $grant = $server->group->tool?->slug ?? $server->group->grant;

            return [
                'group' => $grant,
                'website_id' => $server->website_id,
                'domain' => $server->domain,
                'secret_key' => $server->secret_key ?: $this->defaultSecretForGroup($server->group->slug),
            ];
        }

        return config("tool_endpoints.endpoints.{$slug}");
    }

    public function resolveProductIds(User $user): array
    {
        $ids = [];

        foreach (app(SubscriptionService::class)->activeSubscriptions($user) as $sub) {
            if ($sub->plan_id) {
                $ids[] = (int) $sub->plan_id;
            } elseif ($sub->tool_id) {
                $ids[] = (int) $sub->tool_id;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function buttonFromServer(ToolAccessServer $server): array
    {
        if ($server->isDirect()) {
            return [
                'type' => 'direct',
                'url' => $server->direct_url,
                'label' => $server->label,
            ];
        }

        if ($server->isExtension()) {
            return [
                'type' => 'extension',
                'slug' => $server->slug,
                'tool_key' => $server->extension_tool_key ?: $server->slug,
                'label' => $server->label,
            ];
        }

        return [
            'type' => 'proxy',
            'slug' => $server->slug,
            'label' => $server->label,
        ];
    }

    protected function defaultSecretForGroup(string $groupSlug): string
    {
        return match ($groupSlug) {
            'ahrefs' => env('TOOL_SECRET_AHREFS', 'prideref_ahrefs_secret_xyz123'),
            default => env('TOOL_SECRET_SEMRUSH', 'toolsmandi_recloudsemrush_secret_xyz123'),
        };
    }
}
