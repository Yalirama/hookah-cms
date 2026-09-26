<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatusHistory;

class OrderObserver
{
    public function created(Order $order): void
    {
        OrderStatusHistory::create([
            'order_id'   => $order->id,
            'status'     => $order->status,
            'started_at' => $order->created_at,
        ]);
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status')) {
            OrderStatusHistory::create([
                'order_id'   => $order->id,
                'status'     => $order->status,
                'started_at' => now(),
            ]);
        }
    }
}