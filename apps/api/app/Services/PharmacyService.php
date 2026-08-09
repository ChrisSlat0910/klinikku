<?php

namespace App\Services;

use App\Events\PrescriptionReady;
use App\Models\Drug;
use App\Models\DrugStockMovement;
use App\Models\Prescription;
use App\Models\User;

class PharmacyService
{
    public function dispense(Prescription $prescription, User $apoteker): Prescription
    {
        // Deduct stock for each prescription item
        foreach ($prescription->items as $item) {
            if ($item->drug_id !== null) {
                $drug = Drug::find($item->drug_id);
                if ($drug instanceof Drug) {
                    if ($drug->stock < $item->quantity) {
                        throw new \RuntimeException("INSUFFICIENT_STOCK:{$drug->name}");
                    }

                    $stockBefore = $drug->stock;
                    $drug->decrement('stock', $item->quantity);

                    DrugStockMovement::create([
                        'drug_id' => $drug->id,
                        'user_id' => $apoteker->id,
                        'type' => 'out',
                        'quantity' => $item->quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $drug->stock,
                        'reference' => "prescription:{$prescription->id}",
                    ]);
                }
            }
        }

        $prescription->update([
            'status' => 'dispensed',
            'dispensed_by' => $apoteker->id,
            'dispensed_at' => now(),
        ]);

        // Broadcast PrescriptionReady to kasir
        broadcast(new PrescriptionReady(
            $prescription->clinic_id,
            $prescription->id,
            $prescription->patient !== null ? $prescription->patient->name : 'Pasien',
            '-'
        ));

        return $prescription->fresh(['items', 'patient', 'doctor']);
    }

    public function adjustStock(Drug $drug, User $user, string $type, int $quantity, ?string $notes): Drug
    {
        $stockBefore = $drug->stock;

        if ($type === 'in') {
            $drug->increment('stock', $quantity);
        } elseif ($type === 'out') {
            if ($drug->stock < $quantity) {
                throw new \RuntimeException('INSUFFICIENT_STOCK');
            }
            $drug->decrement('stock', $quantity);
        } else {
            $drug->update(['stock' => $quantity]);
        }

        DrugStockMovement::create([
            'drug_id' => $drug->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $drug->stock,
            'notes' => $notes,
        ]);

        return $drug->fresh();
    }
}
