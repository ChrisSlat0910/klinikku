<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    protected $fillable = [
        'clinic_id',
        'queue_item_id',
        'patient_id',
        'doctor_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'tindakan',
        'vital_signs',
    ];

    protected $casts = [
        'tindakan' => 'array',
        'vital_signs' => 'array',
    ];

    /** @return BelongsTo<Clinic, MedicalRecord> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<Patient, MedicalRecord> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, MedicalRecord> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return HasMany<Prescription> */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
