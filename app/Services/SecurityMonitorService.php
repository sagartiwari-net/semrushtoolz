<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Support\ClientIp;
use App\Support\DeviceFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SecurityMonitorService
{
    public function logActivity(User $user, Request $request, string $action = 'page_view'): UserLoginLog
    {
        $parsed = $this->parseUserAgent($request->userAgent() ?? '');

        $log = UserLoginLog::create([
            'user_id' => $user->id,
            'ip_address' => ClientIp::from($request),
            'user_agent' => $request->userAgent(),
            'device_type' => $parsed['device'],
            'browser' => $parsed['browser'],
            'platform' => $parsed['platform'],
            'device_fingerprint' => DeviceFingerprint::fromRequest($request),
            'route' => $request->route()?->getName(),
            'action' => $action,
            'logged_at' => now(),
        ]);

        $this->analyzeUser($user, $request);

        return $log;
    }

    public function logActivityThrottled(User $user, Request $request, string $action = 'page_view'): ?UserLoginLog
    {
        $minutes = config('security.activity_log_interval_minutes', 5);
        $key = "security:activity:{$user->id}";

        if (Cache::has($key)) {
            return null;
        }

        Cache::put($key, true, now()->addMinutes($minutes));

        return $this->logActivity($user, $request, $action);
    }

    public function bindDeviceToSession(Request $request): void
    {
        $fingerprint = DeviceFingerprint::fromRequest($request);

        if (! $fingerprint) {
            return;
        }

        $request->session()->put(DeviceFingerprint::SESSION_KEY, $fingerprint);
    }

    public function sessionDeviceMatches(Request $request): bool
    {
        $expected = $request->session()->get(DeviceFingerprint::SESSION_KEY);

        if (! $expected) {
            return true;
        }

        $current = DeviceFingerprint::fromRequest($request);

        if (! $current) {
            return true;
        }

        return hash_equals($expected, $current);
    }

    public function analyzeUser(User $user, ?Request $request = null): void
    {
        if ($this->isExempt($user)) {
            return;
        }

        $window = now()->subHour();

        $recentLogs = UserLoginLog::query()
            ->where('user_id', $user->id)
            ->where('logged_at', '>=', $window)
            ->get(['ip_address', 'device_fingerprint']);

        $uniqueIps = $recentLogs->pluck('ip_address')->filter()->unique()->values();
        $uniqueDevices = $recentLogs->pluck('device_fingerprint')->filter()->unique()->values();

        $ipCount = $uniqueIps->count();
        $deviceCount = $uniqueDevices->count();

        $warnIps = config('security.max_ips_warning_hour', 3);
        $blockIps = config('security.max_ips_block_hour', 5);
        $minDevicesWarn = config('security.min_devices_for_warning', 2);
        $minDevicesBlock = config('security.min_devices_for_block', 2);
        $cooldown = config('security.alert_cooldown_minutes', 60);

        $metadata = [
            'ips' => $uniqueIps->all(),
            'ip_count' => $ipCount,
            'device_count' => $deviceCount,
            'devices' => $uniqueDevices->all(),
            'window' => '1 hour',
        ];

        if ($ipCount >= $blockIps && $deviceCount >= $minDevicesBlock) {
            $this->handleSharingBlock($user, $metadata, $cooldown, $request);

            return;
        }

        if ($ipCount >= $warnIps && $deviceCount >= $minDevicesWarn) {
            $this->handleSharingWarning($user, $metadata, $cooldown, $request);
        }

        $this->analyzeLegacyIpThresholds($user, $cooldown);

        $user->update(['last_ip_check_at' => now()]);
    }

    protected function handleSharingWarning(User $user, array $metadata, int $cooldown, ?Request $request): void
    {
        $alert = $this->createAlertIfNeeded(
            $user,
            'ip_sharing_warning',
            'high',
            array_merge($metadata, [
                'reason' => 'Multiple IPs and devices in 1 hour — possible account sharing',
            ]),
            $cooldown,
        );

        if ($alert && $request) {
            $request->session()->flash(
                'security_warning',
                'Unusual activity detected: your account was accessed from multiple locations/devices in the last hour. '
                .'If this was not you, change your password and contact support.',
            );
        }
    }

    protected function handleSharingBlock(User $user, array $metadata, int $cooldown, ?Request $request): void
    {
        if (! config('security.auto_block_on_sharing', true)) {
            $this->handleSharingWarning($user, $metadata, $cooldown, $request);

            return;
        }

        $recentBlock = SecurityAlert::query()
            ->where('user_id', $user->id)
            ->where('type', 'ip_sharing_blocked')
            ->where('created_at', '>=', now()->subMinutes($cooldown))
            ->exists();

        if ($recentBlock) {
            return;
        }

        $ipList = implode(', ', $metadata['ips'] ?? []);

        SecurityAlert::create([
            'user_id' => $user->id,
            'type' => 'ip_sharing_blocked',
            'severity' => 'critical',
            'title' => 'Auto-blocked — '.$user->name,
            'description' => "User {$user->email} accessed from {$metadata['ip_count']} IP(s) and {$metadata['device_count']} device(s) in 1 hour. IPs: {$ipList}",
            'metadata' => $metadata,
            'status' => 'open',
        ]);

        $user->increment('security_alert_count');

        $this->blockUser(
            $user,
            'Automatic block: account accessed from '.$metadata['ip_count'].' IPs on '.$metadata['device_count'].' devices within 1 hour.',
        );
    }

    protected function analyzeLegacyIpThresholds(User $user, int $cooldown): void
    {
        $maxDaily = config('security.max_ips_per_day', 8);
        $maxHourly = config('security.max_ips_per_hour', 6);

        $ipsLast24h = UserLoginLog::query()
            ->where('user_id', $user->id)
            ->where('logged_at', '>=', now()->subDay())
            ->distinct()
            ->pluck('ip_address');

        $ipsLastHour = UserLoginLog::query()
            ->where('user_id', $user->id)
            ->where('logged_at', '>=', now()->subHour())
            ->distinct()
            ->pluck('ip_address');

        if ($ipsLastHour->count() >= $maxHourly) {
            $this->createAlertIfNeeded($user, 'multiple_ip_hourly', 'high', [
                'ips' => $ipsLastHour->values()->all(),
                'count' => $ipsLastHour->count(),
                'window' => '1 hour',
            ], $cooldown);
        }

        if ($ipsLast24h->count() >= $maxDaily) {
            $this->createAlertIfNeeded($user, 'multiple_ip_daily', 'critical', [
                'ips' => $ipsLast24h->values()->all(),
                'count' => $ipsLast24h->count(),
                'window' => '24 hours',
            ], $cooldown);

            $this->createAlertIfNeeded($user, 'account_sharing', 'critical', [
                'ips' => $ipsLast24h->values()->all(),
                'reason' => 'Multiple unique IPs detected — possible account sharing',
            ], $cooldown);
        }
    }

    public function createAlertIfNeeded(
        User $user,
        string $type,
        string $severity,
        array $metadata,
        int $cooldownMinutes
    ): ?SecurityAlert {
        $recent = SecurityAlert::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('status', 'open')
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->exists();

        if ($recent) {
            return null;
        }

        $typeLabel = config("security.alert_types.{$type}", $type);
        $count = $metadata['count'] ?? $metadata['ip_count'] ?? count($metadata['ips'] ?? []);
        $ipList = implode(', ', $metadata['ips'] ?? []);
        $window = $metadata['window'] ?? 'recent activity';
        $deviceCount = $metadata['device_count'] ?? null;

        $description = $deviceCount !== null
            ? "User {$user->email} accessed from {$count} IP(s) and {$deviceCount} device(s) in {$window}. IPs: {$ipList}"
            : "User {$user->email} accessed from {$count} different IP(s) in {$window}. IPs: {$ipList}";

        $alert = SecurityAlert::create([
            'user_id' => $user->id,
            'type' => $type,
            'severity' => $severity,
            'title' => "{$typeLabel} — {$user->name}",
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'open',
        ]);

        $user->increment('security_alert_count');

        return $alert;
    }

    public function blockUser(User $user, string $reason, ?int $adminId = null): void
    {
        $user->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'block_reason' => $reason,
            'blocked_by' => $adminId,
        ]);

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    public function unblockUser(User $user): void
    {
        $user->update([
            'status' => 'active',
            'blocked_at' => null,
            'block_reason' => null,
            'blocked_by' => null,
        ]);
    }

    public function getUserIpSummary(int $userId): array
    {
        $logs = UserLoginLog::query()
            ->where('user_id', $userId)
            ->where('logged_at', '>=', now()->subDays(7))
            ->orderByDesc('logged_at')
            ->get();

        $uniqueIps = $logs->pluck('ip_address')->unique()->values();

        return [
            'total_requests' => $logs->count(),
            'unique_ips' => $uniqueIps->count(),
            'ips' => $uniqueIps->all(),
            'recent_logs' => $logs->take(20),
            'ip_breakdown' => $logs->groupBy('ip_address')->map(fn ($group) => [
                'count' => $group->count(),
                'last_seen' => $group->first()->logged_at,
                'devices' => $group->pluck('device_type')->unique()->values()->all(),
                'fingerprints' => $group->pluck('device_fingerprint')->filter()->unique()->values()->all(),
            ]),
        ];
    }

    protected function isExempt(User $user): bool
    {
        return in_array($user->role, config('security.exempt_admin_roles', []), true);
    }

    protected function parseUserAgent(string $ua): array
    {
        $device = 'desktop';
        if (preg_match('/mobile|android|iphone|ipad/i', $ua)) {
            $device = preg_match('/ipad|tablet/i', $ua) ? 'tablet' : 'mobile';
        }

        $browser = 'Unknown';
        foreach (['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'] as $b) {
            if (stripos($ua, $b) !== false) {
                $browser = $b;
                break;
            }
        }

        $platform = 'Unknown';
        foreach (['Windows', 'Mac', 'Linux', 'Android', 'iOS'] as $p) {
            if (stripos($ua, $p) !== false) {
                $platform = $p;
                break;
            }
        }

        return compact('device', 'browser', 'platform');
    }
}
