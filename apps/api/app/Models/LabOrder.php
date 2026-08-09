<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabOrder extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'medical_record_id',
        'patient_id',
        'doctor_id',
        'tests_requested',
        'status',
        'clinical_notes',
    ];

    protected $casts = [
        'tests_requested' => 'array',
    ];

    /** @return BelongsTo<Clinic, LabOrder> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<Patient, LabOrder> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, LabOrder> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return HasOne<LabResult> */
    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }
}
