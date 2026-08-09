<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'password',
        'nik',
        'phone',
        'position',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** @return BelongsTo<Clinic, User> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return HasMany<StaffSchedule> */
    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    /** @return HasMany<QueueItem> */
    public function queueItems(): HasMany
    {
        return $this->hasMany(QueueItem::class, 'doctor_id');
    }
}
