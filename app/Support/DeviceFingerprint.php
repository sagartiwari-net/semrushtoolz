<?php

namespace App\Support;

use Illuminate\Http\Request;

class DeviceFingerprint
{
    public const COOKIE_NAME = 'semrush_device_fp';

    public const SESSION_KEY = 'auth.device_fp';

    public static function fromRequest(Request $request): ?string
    {
        $raw = $request->header('X-Device-Fingerprint')
            ?? $request->input('device_fingerprint')
            ?? $request->cookie(self::COOKIE_NAME);

        if (! is_string($raw) || ! static::isValid($raw)) {
            return static::fallbackFromUserAgent($request->userAgent() ?? '');
        }

        return $raw;
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    /**
     * Weak fallback when JS fingerprint is unavailable (no block decisions on this alone).
     */
    public static function fallbackFromUserAgent(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        return hash('sha256', 'ua:'.mb_strtolower($userAgent));
    }
}
