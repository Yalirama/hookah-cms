<?php

namespace App\Support;

class Plural
{
    public static function ru(int $n, string $one, string $few, string $many): string
    {
        $a = abs($n) % 100;
        $b = $a % 10;
        if ($a > 10 && $a < 20) return $many;
        if ($b === 1) return $one;
        if ($b >= 2 && $b <= 4) return $few;

        return $many;
    }
}
