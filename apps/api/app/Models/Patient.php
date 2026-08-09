<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $fillable = [
        'clinic_id',
        'name',
        'phone',
        'nik',
        'dob',
        'gender',
        'allergies',
        'chronic_conditions',
        'notes',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    /** @return BelongsTo<Clinic, Patient> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return HasOne<PatientAccount> */
    public function account(): HasOne
    {
        return $this->hasOne(PatientAccount::class);
    }

    /** @return HasMany<PatientConsent> */
    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    /** @return HasMany<QueueItem> */
    public function queueItems(): HasMany
    {
        return $this->hasMany(QueueItem::class);
    }

    /** @return HasMany<MedicalRecord> */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
