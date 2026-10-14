<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use App\Services\PushNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PushServiceReminders extends Command
{
    protected $signature = 'push:service-reminders';
    protected $description = 'Push reminders for orders waiting for service';

    public function handle(): void
    {
        if (Setting::where('key', 'service_reminder_enabled')->value('value') !== '1') return;

        $interval = max(1, (int) (Setting::where('key', 'service_reminder_interval')->value('value') ?? 20));

        $orders = Order::where('status', 'served')
            ->with(['table', 'logs' => fn($q) => $q->whereIn('action', ['status_changed', 'serviced'])])
            ->get();

        foreach ($orders as $order) {
            $last = $order->logs->sortByDesc('created_at')->first(
                fn($l) => $l->action === 'serviced'
                    || ($l->action === 'status_changed' && ($l->new_value['status'] ?? '') === 'served')
            );
            if (!$last) continue;

            $minutes = (int) $last->created_at->diffInMinutes(now());
            $step = intdiv($minutes, $interval);
            if ($step < 1) continue;

            if (!Cache::add("service_push:{$order->id}:{$last->created_at->timestamp}:{$step}", 1, now()->addHours(6))) continue;

            PushNotifier::toAllExcept(null, [
                'title' => 'Пора сервис, заказ #' . $order->id,
                'body'  => trim(($order->table?->name ?? '') . ' · ' . $minutes . ' мин у гостя', ' ·'),
                'url'   => route('orders.index', ['open' => $order->id], false),
                'tag'   => 'order-service-' . $order->id,
            ]);
        }
    }
}