<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Rme\CreateRmeRequest;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Services\RmeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RmeController extends BaseController
{
    public function __construct(private RmeService $rmeService) {}

    public function index(Request $request, int $patientId): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $patient = Patient::where('clinic_id', $clinicId)->find($patientId);

        if (! $patient instanceof Patient) {
            // Check cross-clinic access with consent
            $patient = Patient::withoutGlobalScope('clinic')->find($patientId);

            if (! $patient instanceof Patient) {
                return $this->notFound('Patient not found.');
            }

            if (! $this->rmeService->canAccessRme($clinicId, $patientId)) {
                return $this->error('CONSENT_REQUIRED', 'Patient has not granted consent for cross-clinic record access.', 403);
            }
        }

        $records = MedicalRecord::withoutGlobalScope('clinic')
            ->where('patient_id', $patientId)
            ->with(['clinic', 'doctor', 'diagnoses'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->paginated($records->items(), [
            'page' => $records->currentPage(),
            'per_page' => $records->perPage(),
            'total' => $records->total(),
            'last_page' => $records->lastPage(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $rme = MedicalRecord::where('clinic_id', $clinicId)
            ->with(['patient', 'doctor', 'diagnoses', 'prescriptions.items', 'referral', 'labOrders.result'])
            ->find($id);

        if (! $rme instanceof MedicalRecord) {
            return $this->notFound('Medical record not found.');
        }

        return $this->success($rme);
    }

    public function store(CreateRmeRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $rme = $this->rmeService->createRme(
            $clinicId,
            $user->id,
            $request->validated()
        );

        return $this->success($rme, 201);
    }
}
