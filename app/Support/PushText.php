<?php

namespace App\Support;

use App\Models\Order;

class PushText
{
    public static function created(Order $order): array
    {
        $order->loadMissing('table', 'guest');

        return [
            'title' => 'Новый заказ · ' . self::table($order),
            'body'  => self::join(['Заказ #' . $order->id, $order->guest ? 'гость ' . $order->guest->name : null]),
        ];
    }

    public static function ready(Order $order): array
    {
        $order->loadMissing('table', 'guest');
        $names = $order->items()->withoutTrashed()->with('itemable')->get()->map(fn ($i) => $i->itemable?->name)->filter()->values();
        $list = $names->take(2)->implode(', ') . ($names->count() > 2 ? ' +' . ($names->count() - 2) : '');

        return [
            'title' => 'Готово · ' . self::table($order),
            'body'  => self::join(['Заказ #' . $order->id, $list ?: null, 'несите гостю']),
        ];
    }

    public static function service(Order $order, int $minutes): array
    {
        $order->loadMissing('table');

        return [
            'title' => 'Пора на сервис · ' . self::table($order),
            'body'  => 'Заказ #' . $order->id . ' · у гостя ' . ($minutes >= 60 ? intdiv($minutes, 60) . ' ч ' . ($minutes % 60) . ' мин' : $minutes . ' мин'),
        ];
    }

    private static function table(Order $order): string
    {
        return $order->table?->name ?? 'без стола';
    }

    private static function join(array $parts): string
    {
        return implode(' · ', array_filter($parts));
    }
}
