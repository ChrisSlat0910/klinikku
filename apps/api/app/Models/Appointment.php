<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'patient_id',
        'queue_item_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    /** @return BelongsTo<Clinic, Appointment> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<User, Appointment> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return BelongsTo<Patient, Appointment> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
