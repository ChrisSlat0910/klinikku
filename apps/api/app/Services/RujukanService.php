<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Referral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RujukanService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $clinicId, int $doctorId, array $data): Referral
    {
        $digitalCode = $this->generateDigitalCode();
        $expiresAt = Carbon::now()->addDays(30);

        $referral = Referral::create([
            'clinic_id' => $clinicId,
            'medical_record_id' => $data['medical_record_id'],
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctorId,
            'digital_code' => $digitalCode,
            'destination_facility' => $data['destination_facility'],
            'destination_specialty' => $data['destination_specialty'],
            'urgency' => $data['urgency'],
            'reason' => $data['reason'],
            'summary' => $data['summary'] ?? null,
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);

        $referral->load(['patient', 'doctor', 'medicalRecord']);

        // Send WA notification with digital code
        $patient = $referral->patient;
        $doctor = $referral->doctor;

        if ($patient instanceof Patient && $doctor instanceof User) {
            $urgencyLabel = match ($data['urgency']) {
                'emergency' => 'DARURAT',
                'urgent' => 'Segera',
                default => 'Rutin',
            };

            $message = "Rujukan Digital dari {$doctor->name}\n"
                ."Kode Rujukan: {$digitalCode}\n"
                ."Fasilitas Tujuan: {$data['destination_facility']}\n"
                ."Spesialisasi: {$data['destination_specialty']}\n"
                ."Prioritas: {$urgencyLabel}\n"
                ."Berlaku hingga: {$expiresAt->format('d M Y')}\n"
                .'Tunjukkan kode ini ke fasilitas kesehatan tujuan.';

            $this->notificationService->sendWa(
                $clinicId,
                $patient->phone,
                'referral_delivered',
                $message
            );
        }

        return $referral;
    }

    private function generateDigitalCode(): string
    {
        do {
            $code = 'RJK-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Referral::withoutGlobalScope('clinic')->where('digital_code', $code)->exists());

        return $code;
    }
}
