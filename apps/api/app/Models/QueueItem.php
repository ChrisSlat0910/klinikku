<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QueueItem extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'patient_id',
        'queue_number',
        'token',
        'position',
        'status',
        'patient_name',
        'patient_phone',
        'chief_complaint',
        'vital_signs',
        'called_at',
        'completed_at',
        'queue_date',
    ];

    protected $casts = [
        'vital_signs' => 'array',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
        'queue_date' => 'date',
    ];

    /** @return BelongsTo<Clinic, QueueItem> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<User, QueueItem> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return BelongsTo<Patient, QueueItem> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return HasOne<Invoice> */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /** @return HasOne<MedicalRecord> */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }
}
