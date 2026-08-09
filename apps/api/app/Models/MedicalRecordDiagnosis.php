<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordDiagnosis extends Model
{
    protected $fillable = [
        'medical_record_id',
        'diagnosis_id',
        'type',
    ];

    /** @return BelongsTo<MedicalRecord, MedicalRecordDiagnosis> */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /** @return BelongsTo<Diagnosis, MedicalRecordDiagnosis> */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }
}
