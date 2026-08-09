<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAccount extends Model
{
    protected $fillable = [
        'patient_id',
        'email',
        'password',
        'google_id',
        'fcm_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** @return BelongsTo<Patient, PatientAccount> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
