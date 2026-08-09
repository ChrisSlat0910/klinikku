<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Rujukan\CreateRujukanRequest;
use App\Models\Referral;
use App\Models\User;
use App\Services\RujukanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RujukanController extends BaseController
{
    public function __construct(private RujukanService $rujukanService) {}

    public function index(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = Referral::where('clinic_id', $clinicId)
            ->with(['patient', 'doctor']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        $referrals = $query->orderByDesc('created_at')->paginate(20);

        return $this->paginated($referrals->items(), [
            'page' => $referrals->currentPage(),
            'per_page' => $referrals->perPage(),
            'total' => $referrals->total(),
            'last_page' => $referrals->lastPage(),
        ]);
    }

    public function store(CreateRujukanRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $referral = $this->rujukanService->create(
            $clinicId,
            $user->id,
            $request->validated()
        );

        return $this->success($referral, 201);
    }
}
