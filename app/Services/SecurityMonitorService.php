<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\User;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityMonitorService
{
    public function logActivity(User $user, Request $request, string $action = 'page_view'): UserLoginLog
    {
        $parsed = $this->parseUserAgent($request->userAgent() ?? '');

        $log = UserLoginLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'device_type' => $parsed['device'],
            'browser' => $parsed['browser'],
            'platform' => $parsed['platform'],
            'route' => $request->route()?->getName(),
            'action' => $action,
            'logged_at' => now(),
        ]);

        $this->analyzeUser($user);

        return $log;
    }

    public function analyzeUser(User $user): void
    {
        $maxDaily = config('security.max_ips_per_day', 3);
        $maxHourly = config('security.max_ips_per_hour', 2);
        $cooldown = config('security.alert_cooldown_minutes', 60);

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

        $user->update(['last_ip_check_at' => now()]);
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
        $count = $metadata['count'] ?? count($metadata['ips'] ?? []);
        $ipList = implode(', ', $metadata['ips'] ?? []);
        $window = $metadata['window'] ?? 'recent activity';

        $alert = SecurityAlert::create([
            'user_id' => $user->id,
            'type' => $type,
            'severity' => $severity,
            'title' => "{$typeLabel} — {$user->name}",
            'description' => "User {$user->email} accessed from {$count} different IP(s) in {$window}. IPs: {$ipList}",
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
            ]),
        ];
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
