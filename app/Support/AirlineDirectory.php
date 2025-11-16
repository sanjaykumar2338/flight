<?php

namespace App\Support;

class AirlineDirectory
{
    public static function name(?string $code, ?string $fallback = null): ?string
    {
        if (!$code) {
            return $fallback;
        }

        $lookup = config('airlines', []);
        $upper = strtoupper(trim($code));

        return $lookup[$upper] ?? $fallback ?? $upper;
    }
}
