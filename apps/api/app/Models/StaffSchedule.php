<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSchedule extends Model
{
    protected $fillable = [
        'clinic_id',
        'user_id',
        'poli_name',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Clinic, StaffSchedule> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<User, StaffSchedule> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
