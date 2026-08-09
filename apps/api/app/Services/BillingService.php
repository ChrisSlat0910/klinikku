<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\QueueItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function getOrCreateInvoice(QueueItem $queueItem): Invoice
    {
        $existing = Invoice::where('queue_item_id', $queueItem->id)
            ->where('status', 'pending')
            ->first();

        if ($existing instanceof Invoice) {
            return $existing->load(['items', 'patient']);
        }

        return DB::transaction(function () use ($queueItem): Invoice {
            $invoice = Invoice::create([
                'clinic_id' => $queueItem->clinic_id,
                'queue_item_id' => $queueItem->id,
                'patient_id' => $queueItem->patient_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'subtotal' => 0,
                'discount' => 0,
                'total' => 0,
                'status' => 'pending',
            ]);

            // Add consultation fee
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'consultation',
                'description' => 'Biaya konsultasi',
                'quantity' => 1,
                'unit_price' => 75000,
                'subtotal' => 75000,
            ]);

            // Add drug items from dispensed prescriptions
            $medicalRecord = $queueItem->medicalRecord;
            if ($medicalRecord !== null) {
                $prescriptions = $medicalRecord->prescriptions()
                    ->where('status', 'dispensed')
                    ->with('items')
                    ->get();

                foreach ($prescriptions as $prescription) {
                    foreach ($prescription->items as $item) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'type' => 'drug',
                            'description' => $item->drug_name,
                            'quantity' => $item->quantity,
                            'unit_price' => 5000,
                            'subtotal' => $item->quantity * 5000,
                        ]);
                    }
                }
            }

            $subtotal = InvoiceItem::where('invoice_id', $invoice->id)->sum('subtotal');
            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $invoice->load(['items', 'patient']);
        });
    }

    public function processPayment(Invoice $invoice, User $kasir, string $paymentMethod, float $discount = 0): Invoice
    {
        if ($invoice->status === 'paid') {
            throw new \RuntimeException('ALREADY_PAID');
        }

        $total = (float) $invoice->subtotal - $discount;

        $invoice->update([
            'cashier_id' => $kasir->id,
            'payment_method' => $paymentMethod,
            'discount' => $discount,
            'total' => $total,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $invoice->load(['items', 'patient', 'clinic']);

        // Generate PDF kwitansi
        $this->generateKwitansi($invoice);

        return $invoice->fresh(['items', 'patient']);
    }

    private function generateKwitansi(Invoice $invoice): void
    {
        try {
            $pdf = Pdf::loadView('pdf.kwitansi', ['invoice' => $invoice]);
            $path = storage_path('app/public/kwitansi/'.$invoice->invoice_number.'.pdf');

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $pdf->save($path);

            $invoice->update(['pdf_path' => 'kwitansi/'.$invoice->invoice_number.'.pdf']);
        } catch (\Exception $e) {
            // PDF generation failure should not block payment
            Log::error('Kwitansi PDF generation failed: '.$e->getMessage());
        }
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $count = Invoice::where('invoice_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
