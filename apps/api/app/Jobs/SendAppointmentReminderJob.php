<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $appointmentId) {}

    public function handle(NotificationService $notificationService): void
    {
        $appointment = Appointment::with(['patient', 'doctor'])->find($this->appointmentId);

        if ($appointment === null || $appointment->status !== 'confirmed') {
            return;
        }

        $patient = $appointment->patient;
        $doctor = $appointment->doctor;

        if ($patient === null || $doctor === null) {
            return;
        }

        $scheduledAt = Carbon::parse($appointment->scheduled_at);
        $message = "Pengingat Janji Temu!\n"
            ."Halo {$patient->name}, besok Anda memiliki janji temu dengan {$doctor->name}.\n"
            ."Waktu: {$scheduledAt->format('d M Y H:i')}\n"
            .'Harap datang 10 menit lebih awal.';

        $notificationService->sendWa(
            $appointment->clinic_id,
            $patient->phone,
            'appointment_reminder',
            $message
        );
    }
}
