<?php

namespace App\Services;

use App\Jobs\SendPush;
use App\Models\User;

class PushNotifier
{
    public static function toAllExcept(?int $exceptId, array $payload): void
    {
        $ids = User::where('is_active', true)
            ->where('on_shift', true)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->pluck('id');

        self::send($ids, $payload);
    }

    public static function send($ids, array $payload): void
    {
        $ids = collect($ids)->values();
        if ($ids->isEmpty()) return;

        try {
            SendPush::dispatch($ids->all(), $payload);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}