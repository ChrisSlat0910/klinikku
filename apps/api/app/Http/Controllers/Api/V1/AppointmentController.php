<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Appointment\BookAppointmentRequest;
use App\Http\Requests\Appointment\GetSlotsRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    public function __construct(private AppointmentService $appointmentService) {}

    public function slots(GetSlotsRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $validated = $request->validated();

        $slots = $this->appointmentService->getAvailableSlots(
            $clinicId,
            (int) $validated['doctor_id'],
            $validated['date']
        );

        return $this->success($slots);
    }

    public function index(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = Appointment::where('clinic_id', $clinicId);

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->with(['patient', 'doctor'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return $this->paginated($appointments->items(), [
            'page' => $appointments->currentPage(),
            'per_page' => $appointments->perPage(),
            'total' => $appointments->total(),
            'last_page' => $appointments->lastPage(),
        ]);
    }

    public function store(BookAppointmentRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $validated = $request->validated();

        try {
            $appointment = $this->appointmentService->book($clinicId, $validated);

            return $this->success($appointment, 201);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'SLOT_NO_LONGER_AVAILABLE') {
                return $this->error('SLOT_NO_LONGER_AVAILABLE', 'This slot has just been booked. Please choose another time.', 409);
            }

            throw $e;
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $appointment = Appointment::where('clinic_id', $clinicId)->find($id);

        if (! $appointment instanceof Appointment) {
            return $this->notFound('Appointment not found.');
        }

        if ($appointment->status !== 'confirmed') {
            return $this->error('CONFLICT', 'Only confirmed appointments can be cancelled.', 409);
        }

        $reason = $request->input('reason', 'Cancelled by staff.');

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        return $this->success($appointment);
    }
}
