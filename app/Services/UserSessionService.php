<?php

namespace App\Services;

use App\Models\User;
use App\Support\ClientIp;
use App\Support\DeviceFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserSessionService
{
    public function activeSessions(int $userId, ?string $exceptSessionId = null): Collection
    {
        $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();

        $query = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $cutoff)
            ->orderByDesc('last_activity');

        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->get();
    }

    public function syncSessionMeta(Request $request, ?User $user = null): void
    {
        $sessionId = $request->session()->getId();
        $fingerprint = DeviceFingerprint::clientFromRequest($request);

        $payload = [
            'ip_address' => ClientIp::from($request),
            'user_agent' => $request->userAgent(),
            'last_activity' => now()->getTimestamp(),
        ];

        if ($fingerprint) {
            $payload['device_fingerprint'] = $fingerprint;
        }

        if ($user) {
            $payload['user_id'] = $user->id;
        }

        DB::table('sessions')->where('id', $sessionId)->update($payload);
    }

    public function purgeOtherSessions(int $userId, string $keepSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $keepSessionId)
            ->delete();
    }

    public function killSession(string $sessionId): bool
    {
        return DB::table('sessions')->where('id', $sessionId)->delete() > 0;
    }

    public function killAllSessions(int $userId): int
    {
        return DB::table('sessions')->where('user_id', $userId)->delete();
    }

    /**
     * Block login when another device/browser already has an active session.
     */
    public function concurrentLoginMessage(User $user, Request $request): ?string
    {
        if (in_array($user->role, config('security.exempt_admin_roles', []), true)) {
            return null;
        }

        if (config('security.allow_concurrent_sessions', false)) {
            return null;
        }

        $currentId = $request->session()->getId();
        $currentFp = DeviceFingerprint::clientFromRequest($request);
        $others = $this->activeSessions($user->id, $currentId);

        if ($others->isEmpty()) {
            return null;
        }

        $hasLegacyOnly = $others->every(fn ($session) => blank($session->device_fingerprint));

        if ($hasLegacyOnly) {
            return null;
        }

        if ($currentFp) {
            $differentDeviceActive = $others->contains(
                fn ($session) => filled($session->device_fingerprint)
                    && ! hash_equals($session->device_fingerprint, $currentFp),
            );

            if ($differentDeviceActive) {
                return 'Your account is already logged in on another device or browser. '
                    .'Please log out there first, or contact support to reset your session.';
            }

            return null;
        }

        return null;
    }

    public function formatSessionRow(object $session): array
    {
        return [
            'id' => $session->id,
            'ip' => $session->ip_address ?? '—',
            'user_agent' => $session->user_agent ?? '—',
            'fingerprint' => $session->device_fingerprint
                ? substr($session->device_fingerprint, 0, 12).'…'
                : '—',
            'last_activity' => isset($session->last_activity)
                ? \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans()
                : '—',
            'last_activity_at' => isset($session->last_activity)
                ? \Carbon\Carbon::createFromTimestamp($session->last_activity)
                : null,
        ];
    }
}
