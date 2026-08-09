<?php

namespace App\Services;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentService
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableSlots(int $clinicId, int $doctorId, string $date): array
    {
        $slots = [];
        $startHour = 8;
        $endHour = 17;
        $slotDuration = 15;

        $bookedSlots = Appointment::where('clinic_id', $clinicId)
            ->where('doctor_id', $doctorId)
            ->whereDate('scheduled_at', $date)
            ->whereIn('status', ['confirmed'])
            ->get(['scheduled_at', 'duration_minutes']);

        $current = Carbon::parse($date.' '.$startHour.':00:00');
        $end = Carbon::parse($date.' '.$endHour.':00:00');

        while ($current->lt($end)) {
            $slotTime = $current->copy();
            $isBooked = $bookedSlots->contains(function (Appointment $appt) use ($slotTime): bool {
                $apptStart = Carbon::parse($appt->scheduled_at);
                $apptEnd = $apptStart->copy()->addMinutes($appt->duration_minutes ?? 15);

                return $slotTime->gte($apptStart) && $slotTime->lt($apptEnd);
            });

            $slots[] = [
                'time' => $slotTime->format('H:i'),
                'datetime' => $slotTime->toISOString(),
                'available' => ! $isBooked,
            ];

            $current->addMinutes($slotDuration);
        }

        return $slots;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function book(int $clinicId, array $data): Appointment
    {
        // Check slot availability (race condition protection)
        $conflict = Appointment::where('clinic_id', $clinicId)
            ->where('doctor_id', $data['doctor_id'])
            ->where('scheduled_at', $data['scheduled_at'])
            ->where('status', 'confirmed')
            ->exists();

        if ($conflict) {
            throw new \RuntimeException('SLOT_NO_LONGER_AVAILABLE');
        }

        $appointment = Appointment::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $data['doctor_id'],
            'patient_id' => $data['patient_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 15,
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
        ]);

        $appointment->load(['patient', 'doctor']);

        // Send WA confirmation
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $scheduledAt = Carbon::parse($appointment->scheduled_at);

        if ($patient !== null && $doctor !== null) {
            $message = "Janji temu Anda telah dikonfirmasi!\n"
                ."Dokter: {$doctor->name}\n"
                ."Tanggal: {$scheduledAt->format('d M Y H:i')}\n"
                .'Harap datang 10 menit lebih awal.';

            $this->notificationService->sendWa(
                $clinicId,
                $patient->phone,
                'appointment_confirmed',
                $message
            );
        }

        // Schedule H-1 reminder job
        $reminderAt = $scheduledAt->copy()->subDay();

        if ($reminderAt->isFuture()) {
            SendAppointmentReminderJob::dispatch($appointment->id)
                ->delay($reminderAt);
        }

        return $appointment;
    }
}
