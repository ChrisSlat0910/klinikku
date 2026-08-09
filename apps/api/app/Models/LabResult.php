<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'lab_order_id',
        'analysed_by',
        'results',
        'interpretation',
        'pdf_path',
    ];

    protected $casts = [
        'results' => 'array',
    ];

    /** @return BelongsTo<LabOrder, LabResult> */
    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    /** @return BelongsTo<User, LabResult> */
    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analysed_by');
    }
}
