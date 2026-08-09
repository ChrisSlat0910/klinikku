<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    protected $fillable = [
        'patient_id',
        'clinic_id',
        'granted',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** @return BelongsTo<Patient, PatientConsent> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Clinic, PatientConsent> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
