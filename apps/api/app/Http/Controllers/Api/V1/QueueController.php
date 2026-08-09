<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Queue\OnlineQueueRequest;
use App\Http\Requests\Queue\RegisterQueueRequest;
use App\Http\Requests\Queue\TransferQueueRequest;
use App\Http\Requests\Queue\VitalSignsRequest;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\QueueItem;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends BaseController
{
    public function __construct(private QueueService $queueService) {}

    public function index(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = QueueItem::where('clinic_id', $clinicId)
            ->whereDate('queue_date', $request->get('date', now()->toDateString()));

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->with(['patient', 'doctor'])
            ->orderBy('position')
            ->paginate(50);

        return $this->paginated($items->items(), [
            'page' => $items->currentPage(),
            'per_page' => $items->perPage(),
            'total' => $items->total(),
            'last_page' => $items->lastPage(),
        ]);
    }

    public function register(RegisterQueueRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $data = $request->validated();

        if (isset($data['patient_id'])) {
            $patient = Patient::find((int) $data['patient_id']);
            if ($patient instanceof Patient) {
                $data['patient_name'] = $patient->name;
                $data['patient_phone'] = $patient->phone;
            }
        }

        $queueItem = $this->queueService->registerWalkIn(
            $clinicId,
            (int) $data['doctor_id'],
            $data
        );

        return $this->success($queueItem->load(['patient', 'doctor']), 201);
    }

    public function online(OnlineQueueRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        // Online queue only for authenticated patients via PatientAccount
        $patientAccount = PatientAccount::where('id', $user?->getAuthIdentifier())->first();

        if (! $patientAccount instanceof PatientAccount) {
            return $this->error('UNAUTHORIZED', 'Only registered patients can use online queue.', 403);
        }

        $patient = $patientAccount->patient;
        if (! $patient instanceof Patient) {
            return $this->error('PATIENT_NOT_FOUND', 'Patient record not found.', 404);
        }

        $data = $request->validated();

        $queueItem = $this->queueService->registerOnline(
            $clinicId,
            (int) $data['doctor_id'],
            $patient,
            $data
        );

        return $this->success($queueItem->load(['patient', 'doctor']), 201);
    }

    public function call(int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $queueItem = QueueItem::where('clinic_id', $clinicId)->find($id);

        if (! $queueItem instanceof QueueItem) {
            return $this->notFound('Queue item not found.');
        }

        if ($queueItem->status !== 'waiting') {
            return $this->error('CONFLICT', 'Queue item is not in waiting status.', 409);
        }

        $queueItem = $this->queueService->callPatient($queueItem);

        return $this->success($queueItem);
    }

    public function complete(int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $queueItem = QueueItem::where('clinic_id', $clinicId)->find($id);

        if (! $queueItem instanceof QueueItem) {
            return $this->notFound('Queue item not found.');
        }

        if (! in_array($queueItem->status, ['called', 'in_progress'])) {
            return $this->error('CONFLICT', 'Queue item cannot be completed from current status.', 409);
        }

        $queueItem = $this->queueService->completeConsultation($queueItem);

        return $this->success($queueItem);
    }

    public function transfer(TransferQueueRequest $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $queueItem = QueueItem::where('clinic_id', $clinicId)->find($id);

        if (! $queueItem instanceof QueueItem) {
            return $this->notFound('Queue item not found.');
        }

        $newDoctor = User::where('clinic_id', $clinicId)
            ->find((int) $request->validated()['doctor_id']);

        if (! $newDoctor instanceof User) {
            return $this->error('RESOURCE_NOT_FOUND', 'Doctor not found in this clinic.', 404);
        }

        $queueItem = $this->queueService->transferQueue($queueItem, $newDoctor->id);

        return $this->success($queueItem);
    }

    public function vitalSigns(VitalSignsRequest $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $queueItem = QueueItem::where('clinic_id', $clinicId)->find($id);

        if (! $queueItem instanceof QueueItem) {
            return $this->notFound('Queue item not found.');
        }

        $queueItem->update([
            'vital_signs' => $request->validated(),
            'status' => 'in_progress',
        ]);

        return $this->success($queueItem);
    }
}
