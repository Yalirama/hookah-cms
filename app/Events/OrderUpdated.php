<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
       $this->order->load([
            'table.type',
            'items' => fn($q) => $q->where('deleted_at', null)->with('addedBy'),
            'statusHistories',
        ]);

        // Загружаем ingredients только для миксов
        $this->order->items->each(function($item) {
            if (str_contains($item->itemable_type, 'Mix')) {
                $item->itemable?->load('ingredients.tobacco.brand');
            } else {
                $item->load('itemable');
            }
        });
    }

    public function broadcastOn(): array
    {
        return [new Channel('order.' . $this->order->id)];
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

        $activeItems = $this->order->items->map(fn($item) => [
            'id'          => $item->id,
            'type'        => str_contains($item->itemable_type, 'Mix') ? 'mix' : 'tobacco',
            'name'        => $item->itemable?->name ?? '—',
            'grams'       => $item->grams,
            'added_by'    => $item->addedBy?->name ?? '—',
            'created_at'  => $item->created_at->format('d.m H:i'),
            'ingredients' => str_contains($item->itemable_type, 'Mix') && $item->itemable
                ? $item->itemable->ingredients->map(fn($ing) => [
                    'brand' => $ing->tobacco?->brand?->name ?? '—',
                    'name'  => $ing->tobacco?->name ?? '—',
                    'grams' => $ing->grams,
                ])->toArray()
                : [],
        ])->values()->toArray();
                
        return [
            'id'           => $this->order->id,
            'status'       => $this->order->status,
            'status_label' => $statusLabels[$this->order->status] ?? $this->order->status,
            'is_active'    => $this->order->isActive(),
            'table_id'     => $this->order->table_id,
            'table_name'   => $this->order->table?->name ?? '—',
            'table_type'   => $this->order->table?->type?->name,
            'closed_at'    => $this->order->closed_at?->format('d.m.Y H:i:s'),
            'items'        => $activeItems,
            'status_labels'=> $statusLabels,
            'created_at_timestamp' => $this->order->created_at->timestamp,
            'status_histories' => $this->order->statusHistories
                ->filter(fn($h) => !in_array($h->status, ['closed', 'cancelled', 'redo']))
                ->values()
                ->map(fn($h) => [
                    'status'     => $h->status,
                    'started_at' => $h->started_at->timestamp,
                ])->toArray(),
            'is_closed' => in_array($this->order->status, ['closed', 'cancelled', 'redo']),
            'service_reminder_interval' => (int) \App\Models\Setting::where('key', 'service_reminder_interval')->value('value') ?? 20,
            'service_reminder_enabled'  => \App\Models\Setting::where('key', 'service_reminder_enabled')->value('value') === '1',
        ];
    }
}