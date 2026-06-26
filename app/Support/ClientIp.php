<?php

namespace App\Support;

use Illuminate\Http\Request;

class ClientIp
{
    /**
     * Resolve the visitor IP, preferring Cloudflare / reverse-proxy headers when present.
     */
    public static function from(Request $request): string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP'] as $header) {
            $value = $request->headers->get($header);

            if (filled($value)) {
                return static::normalize($value);
            }
        }

        return static::normalize($request->ip() ?? '0.0.0.0');
    }

    protected static function normalize(string $ip): string
    {
        $ip = trim(explode(',', $ip)[0]);

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
