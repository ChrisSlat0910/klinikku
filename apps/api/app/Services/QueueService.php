<?php

namespace App\Services;

use App\Events\DisplayRefresh;
use App\Events\PatientCalled;
use App\Events\QueueUpdated;
use App\Models\Patient;
use App\Models\QueueItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QueueService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerWalkIn(int $clinicId, int $doctorId, array $data): QueueItem
    {
        $queueNumber = $this->generateQueueNumber($clinicId, $doctorId);
        $token = $this->generateToken();

        $queueItem = QueueItem::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $doctorId,
            'patient_id' => $data['patient_id'] ?? null,
            'queue_number' => $queueNumber,
            'token' => $token,
            'position' => $this->getNextPosition($clinicId, $doctorId),
            'status' => 'waiting',
            'patient_name' => $data['patient_name'],
            'patient_phone' => $data['patient_phone'],
            'chief_complaint' => $data['chief_complaint'],
            'queue_date' => now()->toDateString(),
        ]);

        // Send WA notification
        $message = "Halo {$data['patient_name']}! Antrian Anda telah terdaftar.\n"
            ."No. Antrian: {$queueNumber}\n"
            ."Posisi: {$queueItem->position}\n"
            .'Pantau antrian: '.config('app.url')."/q/{$token}";

        $this->notificationService->sendWa(
            $clinicId,
            $data['patient_phone'],
            'queue_registered',
            $message
        );

        $this->broadcastQueueUpdate($clinicId, $doctorId);

        return $queueItem;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerOnline(int $clinicId, int $doctorId, Patient $patient, array $data): QueueItem
    {
        $queueNumber = $this->generateQueueNumber($clinicId, $doctorId);
        $token = $this->generateToken();

        $queueItem = QueueItem::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $doctorId,
            'patient_id' => $patient->id,
            'queue_number' => $queueNumber,
            'token' => $token,
            'position' => $this->getNextPosition($clinicId, $doctorId),
            'status' => 'waiting',
            'patient_name' => $patient->name,
            'patient_phone' => $patient->phone,
            'chief_complaint' => $data['chief_complaint'],
            'queue_date' => now()->toDateString(),
        ]);

        $this->broadcastQueueUpdate($clinicId, $doctorId);

        return $queueItem;
    }

    public function callPatient(QueueItem $queueItem): QueueItem
    {
        $queueItem->update([
            'status' => 'called',
            'called_at' => now(),
        ]);

        $doctor = User::find($queueItem->doctor_id);
        $poliName = $doctor !== null ? $doctor->position ?? 'Poli' : 'Poli';

        broadcast(new PatientCalled(
            $queueItem->clinic_id,
            $queueItem->doctor_id,
            $queueItem->queue_number,
            $queueItem->patient_name,
            $poliName
        ));

        $this->broadcastQueueUpdate($queueItem->clinic_id, $queueItem->doctor_id);

        return $queueItem;
    }

    public function completeConsultation(QueueItem $queueItem): QueueItem
    {
        $queueItem->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        $this->recalculatePositions($queueItem->clinic_id, $queueItem->doctor_id);
        $this->broadcastQueueUpdate($queueItem->clinic_id, $queueItem->doctor_id);

        return $queueItem;
    }

    public function transferQueue(QueueItem $queueItem, int $newDoctorId): QueueItem
    {
        $oldDoctorId = $queueItem->doctor_id;

        $queueItem->update([
            'doctor_id' => $newDoctorId,
            'position' => $this->getNextPosition($queueItem->clinic_id, $newDoctorId),
        ]);

        $this->recalculatePositions($queueItem->clinic_id, $oldDoctorId);
        $this->broadcastQueueUpdate($queueItem->clinic_id, $oldDoctorId);
        $this->broadcastQueueUpdate($queueItem->clinic_id, $newDoctorId);

        return $queueItem;
    }

    private function recalculatePositions(int $clinicId, int $doctorId): void
    {
        $waitingItems = QueueItem::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->where('status', 'waiting')
            ->whereDate('queue_date', now()->toDateString())
            ->orderBy('created_at')
            ->get();

        foreach ($waitingItems as $index => $item) {
            $newPosition = $index + 1;
            $item->update(['position' => $newPosition]);

            // Trigger WA reminders
            if ($newPosition === 3 || $newPosition === 1) {
                $type = $newPosition === 1 ? 'queue_position_1' : 'queue_position_3';
                $urgency = $newPosition === 1 ? 'Segera menuju poli!' : 'Bersiaplah!';
                $message = "Halo {$item->patient_name}! {$urgency}\n"
                    ."Posisi antrian Anda: {$newPosition}\n"
                    ."No. Antrian: {$item->queue_number}";

                $this->notificationService->sendWa(
                    $clinicId,
                    $item->patient_phone,
                    $type,
                    $message
                );
            }

            // Invalidate public tracking cache
            Cache::forget("queue_status:{$item->token}");

            // Push notification via FCM will be implemented in mobile step
            // Log::info("Push notification queued for position {$newPosition}: {$item->patient_name}");
        }
    }

    private function broadcastQueueUpdate(int $clinicId, int $doctorId): void
    {
        $snapshot = $this->getQueueSnapshot($clinicId, $doctorId);

        broadcast(new QueueUpdated($clinicId, $doctorId, $snapshot));
        broadcast(new DisplayRefresh($clinicId, $snapshot));
    }

    /** @return array<string, mixed> */
    public function getQueueSnapshot(int $clinicId, int $doctorId): array
    {
        $cacheKey = "queue_snap:{$clinicId}:{$doctorId}";

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, 30, function () use ($clinicId, $doctorId): array {
            $items = QueueItem::where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->whereDate('queue_date', now()->toDateString())
                ->whereIn('status', ['waiting', 'called', 'in_progress'])
                ->orderBy('position')
                ->get(['id', 'queue_number', 'patient_name', 'status', 'position'])
                ->toArray();

            return [
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'items' => $items,
                'waiting_count' => collect($items)->where('status', 'waiting')->count(),
                'updated_at' => now()->toISOString(),
            ];
        });
    }

    private function generateQueueNumber(int $clinicId, int $doctorId): string
    {
        $count = QueueItem::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->whereDate('queue_date', now()->toDateString())
            ->count();

        return str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function generateToken(): string
    {
        do {
            $token = 'KLK-'.strtoupper(Str::random(8));
        } while (QueueItem::withoutGlobalScope('clinic')->where('token', $token)->exists());

        return $token;
    }

    private function getNextPosition(int $clinicId, int $doctorId): int
    {
        $count = QueueItem::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->where('status', 'waiting')
            ->whereDate('queue_date', now()->toDateString())
            ->count();

        return $count + 1;
    }
}
