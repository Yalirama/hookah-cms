<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->load('creator', 'table');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        $statusLabels = [
            'pending'     => 'Новый',
            'in_progress' => 'В работе',
            'ready'       => 'Готов',
            'served'      => 'У гостя',
            'redo'        => 'Переделка',
            'closed'      => 'Закрыт',
            'cancelled'   => 'Отменён',
        ];

        $this->order->loadMissing(['items' => fn($q) => $q->withTrashed()->with('itemable')]);

        $items = $this->order->items->map(fn($item) => $item->itemable?->name ?? '—')->join(', ');

        return [
            'id'           => $this->order->id,
            'name'         => $this->order->name,
            'status'       => $this->order->status,
            'status_label' => $statusLabels[$this->order->status] ?? $this->order->status,
            'table'        => $this->order->table?->name,
            'master'       => $this->order->creator?->name,
            'items'        => $items ?: '—',
            'created_at'   => $this->order->created_at->format('d.m H:i'),
            'updated_at'   => $this->order->updated_at->format('H:i:s'),
        ];
    }
}