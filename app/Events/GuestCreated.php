<?php

namespace App\Events;

use App\Models\Guest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Guest $guest) {}

    public function broadcastOn(): array
    {
        return [new Channel('guests')];
    }

    public function broadcastAs(): string
    {
        return 'guest.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->guest->id,
            'name'         => $this->guest->name,
            'username'     => $this->guest->username,
            'status'       => $this->guest->status,
            'status_label' => $this->guest->status_label,
            'visits_count' => 0,
            'url'          => route('guests.show', $this->guest),
        ];
    }
}