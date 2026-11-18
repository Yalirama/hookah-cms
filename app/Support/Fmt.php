<?php

namespace App\Support;

class Fmt
{
    public static function money($v): string
    {
        return number_format((float) $v, 0, ',', ' ') . ' ₸';
    }

    public static function num($v, int $d = 0): string
    {
        return number_format((float) $v, $d, ',', ' ');
    }

    public static function delta($v, bool $invert = false): string
    {
        if ($v === null) return '<span class="statDelta">—</span>';
        $cls = $v == 0 ? '' : (($v > 0) !== $invert ? 'up' : 'down');

        return '<span class="statDelta ' . $cls . '">' . ($v > 0 ? '+' : '') . rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',') . '%</span>';
    }

    public static function mins($m): string
    {
        $m = (float) $m;
        if ($m >= 60) return floor($m / 60) . ' ч ' . round(fmod($m, 60)) . ' мин';

        return rtrim(rtrim(number_format($m, 1, ',', ''), '0'), ',') . ' мин';
    }
}
