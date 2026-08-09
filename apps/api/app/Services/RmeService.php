<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\PatientConsent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RmeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createRme(int $clinicId, int $doctorId, array $data): MedicalRecord
    {
        return DB::transaction(function () use ($clinicId, $doctorId, $data): MedicalRecord {
            $rme = MedicalRecord::create([
                'clinic_id' => $clinicId,
                'queue_item_id' => $data['queue_item_id'],
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctorId,
                'subjective' => $data['subjective'],
                'objective' => $data['objective'],
                'assessment' => $data['assessment'],
                'plan' => $data['plan'],
                'tindakan' => $data['tindakan'] ?? null,
            ]);

            // Attach diagnoses
            foreach ($data['diagnoses'] as $diagnosis) {
                $rme->diagnoses()->attach($diagnosis['diagnosis_id'], [
                    'type' => $diagnosis['type'],
                ]);
            }

            return $rme->load(['patient', 'doctor', 'diagnoses', 'prescriptions']);
        });
    }

    public function canAccessRme(int $requestingClinicId, int $patientId): bool
    {
        // Check patient consent for cross-clinic access
        $consent = PatientConsent::where('patient_id', $patientId)
            ->where('clinic_id', $requestingClinicId)
            ->where('granted', true)
            ->first();

        return $consent !== null;
    }

    /**
     * @return array<int, mixed>
     */
    public function searchIcd10(string $query, int $limit = 10): array
    {
        $cacheKey = 'icd10:'.md5($query.$limit);

        /** @var array<int, mixed> */
        return Cache::remember($cacheKey, 86400, function () use ($query, $limit): array {
            return DB::select('
                SELECT id, code, name_id, name_en, category,
                    CASE
                        WHEN code = ? THEN 3
                        WHEN code LIKE ? THEN 2
                        WHEN name_id LIKE ? THEN 1
                        ELSE 0
                    END as relevance
                FROM diagnoses
                WHERE code = ?
                    OR code LIKE ?
                    OR name_id LIKE ?
                    OR name_en LIKE ?
                ORDER BY relevance DESC, code ASC
                LIMIT ?
            ', [
                strtoupper($query),
                strtoupper($query).'%',
                '%'.$query.'%',
                strtoupper($query),
                strtoupper($query).'%',
                '%'.$query.'%',
                '%'.$query.'%',
                $limit,
            ]);
        });
    }
}
