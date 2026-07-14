<?php

namespace App\Services;

use App\Models\Tool;
use App\Models\ToolSession;
use App\Models\User;

class ToolAccessService
{
    public function __construct(
        protected GoProxyClient $proxy,
        protected SubscriptionService $subscriptions
    ) {}

    public function canAccess(User $user, string $toolSlug): bool
    {
        $tool = Tool::where('slug', $toolSlug)->where('is_active', true)->first();
        if (! $tool) {
            return false;
        }

        if (in_array($tool->access_type, [Tool::ACCESS_WHATSAPP, Tool::ACCESS_CREDENTIALS, Tool::ACCESS_EXTENSION], true)) {
            return $this->userGrantsTool($user, $toolSlug);
        }

        return $this->userGrantsTool($user, $toolSlug)
            && $this->userHasActiveSubscription($user);
    }

    public function userHasActiveSubscription(User $user): bool
    {
        return $this->subscriptions->activeSubscriptions($user)->isNotEmpty();
    }

    public function userGrantsTool(User $user, string $toolSlug): bool
    {
        return in_array($toolSlug, $this->subscriptions->grantedToolSlugsForUser($user), true);
    }

    public function hubSlugForTool(string $toolSlug): ?string
    {
        $toolSlug = Tool::resolveSlugAlias($toolSlug);

        $group = \App\Models\ToolAccessGroup::whereHas('tool', fn ($q) => $q->where('slug', $toolSlug))
            ->where('is_active', true)
            ->first();

        if ($group) {
            return $group->slug;
        }

        // Shop variants like Semrush Site Audit grant Semrush → open that hub.
        $tool = Tool::where('slug', $toolSlug)->first();
        $granted = trim((string) ($tool?->grants_tool_slug ?? ''));
        if ($granted !== '' && $granted !== $toolSlug) {
            return $this->hubSlugForTool($granted);
        }

        return null;
    }

    public function endSession(ToolSession $session, string $reason = 'user_ended'): void
    {
        if (! $session->isActive()) {
            return;
        }

        if ($session->proxy_session_id) {
            $this->proxy->endSession($session->proxy_session_id);
        }

        $session->update([
            'status' => 'ended',
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);
    }

    public function activeSession(User $user, string $tool): ?ToolSession
    {
        return $user->toolSessions()
            ->where('tool', $tool)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    public function lastAccessed(User $user, string $tool): ?string
    {
        $session = $user->toolSessions()
            ->where('tool', $tool)
            ->latest('started_at')
            ->first();

        return $session?->started_at?->diffForHumans();
    }

    public function seatLabel(string $tool): ?string
    {
        $info = $this->proxy->seatInfo($tool);
        if (! $info) {
            return null;
        }

        $used = $info['used'] ?? $info['in_use'] ?? null;
        $total = $info['total'] ?? $info['limit'] ?? null;

        if ($used !== null && $total !== null) {
            return "{$used}/{$total}";
        }

        return null;
    }

    public function cleanupStaleSessions(): int
    {
        $maxMinutes = config('tools.session_max_minutes', 120);
        $cutoff = now()->subMinutes($maxMinutes);

        $stale = ToolSession::query()
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->where('started_at', '<', $cutoff)
            ->get();

        foreach ($stale as $session) {
            $this->endSession($session, 'timeout');
        }

        return $stale->count();
    }
}
