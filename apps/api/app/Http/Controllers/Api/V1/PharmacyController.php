<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Pharmacy\AdjustStockRequest;
use App\Http\Requests\Pharmacy\CreateDrugRequest;
use App\Http\Requests\Pharmacy\DispensePrescriptionRequest;
use App\Models\Drug;
use App\Models\Prescription;
use App\Models\User;
use App\Services\PharmacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyController extends BaseController
{
    public function __construct(private PharmacyService $pharmacyService) {}

    public function prescriptions(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = Prescription::where('clinic_id', $clinicId)
            ->with(['patient', 'doctor', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['pending', 'preparing', 'ready']);
        }

        $prescriptions = $query->orderByDesc('created_at')->paginate(20);

        return $this->paginated($prescriptions->items(), [
            'page' => $prescriptions->currentPage(),
            'per_page' => $prescriptions->perPage(),
            'total' => $prescriptions->total(),
            'last_page' => $prescriptions->lastPage(),
        ]);
    }

    public function dispense(DispensePrescriptionRequest $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $prescription = Prescription::where('clinic_id', $clinicId)
            ->with('items')
            ->find($id);

        if (! $prescription instanceof Prescription) {
            return $this->notFound('Prescription not found.');
        }

        if ($prescription->status === 'dispensed') {
            return $this->error('CONFLICT', 'Prescription already dispensed.', 409);
        }

        try {
            $prescription = $this->pharmacyService->dispense($prescription, $user);

            return $this->success($prescription);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            if (str_starts_with($message, 'INSUFFICIENT_STOCK:')) {
                $drugName = str_replace('INSUFFICIENT_STOCK:', '', $message);

                return $this->error('INSUFFICIENT_STOCK', "Insufficient stock for: {$drugName}", 409);
            }

            throw $e;
        }
    }

    public function drugs(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = Drug::where('clinic_id', $clinicId);

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $drugs = $query->orderBy('name')->paginate(50);

        return $this->paginated($drugs->items(), [
            'page' => $drugs->currentPage(),
            'per_page' => $drugs->perPage(),
            'total' => $drugs->total(),
            'last_page' => $drugs->lastPage(),
        ]);
    }

    public function createDrug(CreateDrugRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $validated = $request->validated();

        $drug = Drug::create([
            'clinic_id' => $clinicId,
            'name' => $validated['name'],
            'generic_name' => $validated['generic_name'] ?? null,
            'unit' => $validated['unit'],
            'stock' => (int) $validated['stock'],
            'min_stock' => (int) $validated['min_stock'],
            'price' => (float) $validated['price'],
            'is_active' => true,
        ]);

        return $this->success($drug, 201);
    }

    public function adjustStock(AdjustStockRequest $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $drug = Drug::where('clinic_id', $clinicId)->find($id);

        if (! $drug instanceof Drug) {
            return $this->notFound('Drug not found.');
        }

        $validated = $request->validated();

        try {
            $drug = $this->pharmacyService->adjustStock(
                $drug,
                $user,
                $validated['type'],
                (int) $validated['quantity'],
                $validated['notes'] ?? null
            );

            return $this->success($drug);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_STOCK') {
                return $this->error('INSUFFICIENT_STOCK', 'Stock cannot go below zero.', 409);
            }

            throw $e;
        }
    }
}
