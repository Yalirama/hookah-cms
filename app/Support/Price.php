<?php

namespace App\Support;

use App\Models\Mix;
use App\Models\Setting;
use App\Models\Tobacco;

class Price
{
    public static function forItem($itemable): ?float
    {
        $p = null;
        if ($itemable instanceof Mix) $p = $itemable->price;
        elseif ($itemable instanceof Tobacco) $p = $itemable->venueTobacco?->price;
        if ($p === null || (float) $p <= 0) $p = Setting::get('default_item_price');
        return $p !== null && (float) $p > 0 ? (float) $p : null;
    }
}
