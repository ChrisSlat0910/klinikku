<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaNotification extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'phone',
        'type',
        'message',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /** @return BelongsTo<Clinic, WaNotification> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
