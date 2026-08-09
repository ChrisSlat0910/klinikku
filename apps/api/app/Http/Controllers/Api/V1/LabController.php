<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Lab\CreateLabOrderRequest;
use App\Http\Requests\Lab\SubmitLabResultRequest;
use App\Models\LabOrder;
use App\Models\User;
use App\Services\LabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabController extends BaseController
{
    public function __construct(private LabService $labService) {}

    public function index(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = LabOrder::where('clinic_id', $clinicId)
            ->with(['patient', 'doctor', 'result']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

        return $this->paginated($orders->items(), [
            'page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function store(CreateLabOrderRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $order = $this->labService->createOrder(
            $clinicId,
            $user->id,
            $request->validated()
        );

        return $this->success($order, 201);
    }

    public function submitResult(SubmitLabResultRequest $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $order = LabOrder::where('clinic_id', $clinicId)->find($id);

        if (! $order instanceof LabOrder) {
            return $this->notFound('Lab order not found.');
        }

        if ($order->status === 'completed') {
            return $this->error('CONFLICT', 'Lab result already submitted.', 409);
        }

        $result = $this->labService->submitResult($order, $user, $request->validated());

        return $this->success($result, 201);
    }
}
