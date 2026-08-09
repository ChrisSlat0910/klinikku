<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientCalled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $clinicId,
        public readonly int $doctorId,
        public readonly string $queueNumber,
        public readonly string $patientName,
        public readonly string $poliName
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("queue.{$this->clinicId}.{$this->doctorId}");
    }

    public function broadcastAs(): string
    {
        return 'patient.called';
    }
}
