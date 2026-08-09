<?php

namespace App\Services;

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\User;

class LabService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrder(int $clinicId, int $doctorId, array $data): LabOrder
    {
        $order = LabOrder::create([
            'clinic_id' => $clinicId,
            'medical_record_id' => $data['medical_record_id'],
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctorId,
            'tests_requested' => $data['tests_requested'],
            'clinical_notes' => $data['clinical_notes'] ?? null,
            'status' => 'pending',
        ]);

        return $order->load(['patient', 'doctor']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitResult(LabOrder $order, User $analyst, array $data): LabResult
    {
        $order->update(['status' => 'completed']);

        $result = LabResult::create([
            'lab_order_id' => $order->id,
            'analysed_by' => $analyst->id,
            'results' => $data['results'],
            'interpretation' => $data['interpretation'] ?? null,
        ]);

        // Notify patient via WA if they have a phone
        $patient = $order->patient;
        if ($patient !== null) {
            $testsStr = implode(', ', $order->tests_requested);
            $message = "Hasil laboratorium Anda sudah tersedia.\n"
                ."Pemeriksaan: {$testsStr}\n"
                .'Silakan hubungi klinik atau cek rekam medis Anda untuk detail hasil.';

            $this->notificationService->sendWa(
                $order->clinic_id,
                $patient->phone,
                'lab_result_ready',
                $message
            );
        }

        return $result->load(['analyst', 'labOrder.patient']);
    }
}
