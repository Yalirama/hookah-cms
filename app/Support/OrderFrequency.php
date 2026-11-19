<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class OrderFrequency
{
    private const MONTHS = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];

    public static function of($orderItemsQuery): array
    {
        $rows = (clone $orderItemsQuery)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')
            ->get();

        return self::build($rows);
    }

    public static function ofOrders($ordersQuery): array
    {
        $rows = (clone $ordersQuery)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')
            ->get();

        return self::build($rows);
    }

    private static function build($rows): array
    {
        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r->ym] = (int) $r->c;
        }

        $months = [];
        $start = Carbon::now()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $d = $start->copy()->addMonths($i);
            $months[] = ['label' => self::MONTHS[$d->month - 1], 'count' => $byMonth[$d->format('Y-m')] ?? 0];
        }

        $groups = ['Зима' => [12, 1, 2], 'Весна' => [3, 4, 5], 'Лето' => [6, 7, 8], 'Осень' => [9, 10, 11]];
        $seasons = [];
        foreach ($groups as $label => $nums) {
            $sum = 0;
            foreach ($byMonth as $ym => $c) {
                if (in_array((int) substr($ym, 5, 2), $nums, true)) {
                    $sum += $c;
                }
            }
            $seasons[] = ['label' => $label, 'count' => $sum];
        }

        $byYear = [];
        foreach ($byMonth as $ym => $c) {
            $y = substr($ym, 0, 4);
            $byYear[$y] = ($byYear[$y] ?? 0) + $c;
        }
        ksort($byYear);
        $years = [];
        foreach ($byYear as $y => $c) {
            $years[] = ['label' => (string) $y, 'count' => $c];
        }

        return compact('months', 'seasons', 'years');
    }
}
