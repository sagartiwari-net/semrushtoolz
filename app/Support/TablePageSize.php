<?php

namespace App\Support;

use Illuminate\Http\Request;

class TablePageSize
{
    public const OPTIONS = [10, 20, 50, 100];

    public const DEFAULT = 20;

    public static function resolve(Request $request, string $key = 'per_page'): int
    {
        $value = (int) $request->query($key, self::DEFAULT);

        return in_array($value, self::OPTIONS, true) ? $value : self::DEFAULT;
    }
}
