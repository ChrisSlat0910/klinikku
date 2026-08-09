<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedicalRecord extends Model
{
    use HasClinicScope;

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

    /** @return BelongsTo<QueueItem, MedicalRecord> */
    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(QueueItem::class);
    }

    /** @return HasMany<Prescription> */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /** @return HasOne<Referral> */
    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    /** @return HasMany<LabOrder> */
    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function diagnoses(): BelongsToMany
    {
        return $this->belongsToMany(Diagnosis::class, 'medical_record_diagnoses')
            ->withPivot('type')
            ->withTimestamps();
    }
}
