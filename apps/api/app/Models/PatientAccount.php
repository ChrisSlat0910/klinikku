<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PatientAccount extends Authenticatable
{
    use HasApiTokens, Notifiable;

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
