<?php

namespace App\Support;

use Illuminate\Http\Request;

class DeviceFingerprint
{
    public const COOKIE_NAME = 'semrush_device_fp';

    public const SESSION_KEY = 'auth.device_fp';

    /**
     * Browser fingerprint from cookie / header / form only (used for session binding).
     */
    public static function clientFromRequest(Request $request): ?string
    {
        foreach ([
            $request->header('X-Device-Fingerprint'),
            $request->input('device_fingerprint'),
            $request->cookie(self::COOKIE_NAME),
        ] as $raw) {
            if (is_string($raw) && static::isValid($raw)) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * For activity logs — includes a weak UA fallback when JS fingerprint is missing.
     */
    public static function fromRequest(Request $request): ?string
    {
        return static::clientFromRequest($request)
            ?? static::fallbackFromUserAgent($request->userAgent() ?? '');
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

  public static function fallbackFromUserAgent(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        return hash('sha256', 'ua:'.mb_strtolower($userAgent));
    }
}
