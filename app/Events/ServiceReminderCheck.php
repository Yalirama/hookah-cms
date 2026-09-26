<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ServiceReminderCheck implements ShouldBroadcastNow
{
    public function broadcastOn(): array
    {
        return [new Channel('service-alerts')];
    }

    public function broadcastAs(): string
    {
        return 'service.check';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}