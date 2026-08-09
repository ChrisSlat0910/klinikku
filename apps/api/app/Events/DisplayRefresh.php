<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisplayRefresh implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $displayData
     */
    public function __construct(
        public readonly int $clinicId,
        public readonly array $displayData
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel("display.{$this->clinicId}");
    }

    public function broadcastAs(): string
    {
        return 'display.refresh';
    }
}
