<?php

namespace App\Models;

use App\Traits\HasClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends Model
{
    use HasClinicScope;

    protected $fillable = [
        'clinic_id',
        'name',
        'generic_name',
        'unit',
        'stock',
        'min_stock',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Clinic, Drug> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return HasMany<DrugStockMovement> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(DrugStockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }
}
