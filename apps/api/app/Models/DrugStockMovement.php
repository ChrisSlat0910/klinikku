<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugStockMovement extends Model
{
    protected $fillable = [
        'drug_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference',
        'notes',
    ];

    /** @return BelongsTo<Drug, DrugStockMovement> */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    /** @return BelongsTo<User, DrugStockMovement> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
