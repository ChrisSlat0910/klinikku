<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'queue_item_id',
        'patient_id',
        'cashier_id',
        'invoice_number',
        'subtotal',
        'discount',
        'total',
        'payment_method',
        'status',
        'pdf_path',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /** @return BelongsTo<Clinic, Invoice> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<Patient, Invoice> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<QueueItem, Invoice> */
    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(QueueItem::class);
    }

    /** @return HasMany<InvoiceItem> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
