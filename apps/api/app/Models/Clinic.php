<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'clinic_type',
        'feature_flags',
        'phone',
        'address',
        'wa_token',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'feature_flags' => 'array',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<User> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Patient> */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) ($this->feature_flags[$feature] ?? false);
    }
}
