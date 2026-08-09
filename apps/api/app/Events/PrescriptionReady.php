<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $clinicId,
        public readonly int $prescriptionId,
        public readonly string $patientName,
        public readonly string $queueNumber
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("pharmacy.{$this->clinicId}");
    }

    public function broadcastAs(): string
    {
        return 'prescription.ready';
    }
}
