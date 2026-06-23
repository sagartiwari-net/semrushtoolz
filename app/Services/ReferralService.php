<?php

namespace App\Services;

use App\Models\ReferralClick;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ReferralService
{
    public const COOKIE_NAME = 'semrush_referral_code';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    public function captureFromRequest(Request $request): void
    {
        $code = $this->normalizeCode($request->query('ref'));

        if (! $code || ! $this->isValidCode($code)) {
            return;
        }

        $referrer = $this->resolveReferrer($code);

        if ($referrer && $this->shouldLogClick($referrer->id, $code, $request->ip())) {
            ReferralClick::create([
                'referrer_user_id' => $referrer->id,
                'referral_code' => $code,
                'ip_address' => $request->ip(),
                'landing_path' => '/'.ltrim($request->path(), '/'),
                'user_agent' => \Illuminate\Support\Str::limit((string) $request->userAgent(), 255, ''),
                'clicked_at' => now(),
            ]);
        }

        Cookie::queue(Cookie::make(
            self::COOKIE_NAME,
            $code,
            self::COOKIE_MINUTES,
            '/',
            null,
            false,
            false,
            false,
            'Lax',
        ));
    }

    public function codeFromRequest(Request $request): ?string
    {
        $fromQuery = $this->normalizeCode($request->query('ref'));
        if ($fromQuery && $this->isValidCode($fromQuery)) {
            return $fromQuery;
        }

        $fromCookie = $this->normalizeCode($request->cookie(self::COOKIE_NAME));

        return ($fromCookie && $this->isValidCode($fromCookie)) ? $fromCookie : null;
    }

    public function resolveReferrer(?string $code): ?User
    {
        $code = $this->normalizeCode($code);

        if (! $code) {
            return null;
        }

        return User::query()
            ->where('referral_code', $code)
            ->whereIn('role', ['user', 'admin', 'super_admin'])
            ->first();
    }

    public function isValidCode(?string $code): bool
    {
        $code = $this->normalizeCode($code);

        return $code && User::where('referral_code', $code)->exists();
    }

    public function normalizeCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return filled($code) ? $code : null;
    }

    public function clearCookie(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    protected function shouldLogClick(int $referrerUserId, string $code, ?string $ip): bool
    {
        if (! $ip) {
            return true;
        }

        return ! ReferralClick::query()
            ->where('referrer_user_id', $referrerUserId)
            ->where('referral_code', $code)
            ->where('ip_address', $ip)
            ->where('clicked_at', '>=', now()->subDay())
            ->exists();
    }
}
