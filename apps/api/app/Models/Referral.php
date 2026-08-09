<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'medical_record_id',
        'patient_id',
        'doctor_id',
        'digital_code',
        'destination_facility',
        'destination_specialty',
        'urgency',
        'reason',
        'summary',
        'pdf_path',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /** @return BelongsTo<Clinic, Referral> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<Patient, Referral> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, Referral> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return BelongsTo<MedicalRecord, Referral> */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
