<?php

namespace App\Support;

use Illuminate\Http\Request;

class TablePageSize
{
    public const OPTIONS = [10, 20, 50, 100];

    public const DEFAULT = 20;

    /** Query keys that are page numbers (reset when per_page changes). */
    public const PAGE_KEYS = [
        'page',
        'prov_page',
        'ledger_page',
        'request_page',
        'users_page',
        'upi_page',
        'offline_page',
        'cancelled_page',
    ];

    public static function resolve(Request $request, ?int $default = null, string $key = 'per_page'): int
    {
        $default ??= self::DEFAULT;
        $value = (int) $request->query($key, $default);

        return in_array($value, self::OPTIONS, true) ? $value : $default;
    }
}
