<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Referral;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PublicRujukanController extends BaseController
{
    public function verify(string $code): JsonResponse
    {
        $referral = Referral::withoutGlobalScope('clinic')
            ->where('digital_code', $code)
            ->with(['patient', 'doctor', 'clinic'])
            ->first();

        if (! $referral instanceof Referral) {
            return $this->error('REFERRAL_NOT_FOUND', 'Referral code not found.', 404);
        }

        if ($referral->status === 'expired' || $referral->expires_at !== null && Carbon::now()->gt($referral->expires_at)) {
            $referral->update(['status' => 'expired']);

            return $this->error('REFERRAL_EXPIRED', 'This referral has expired.', 410);
        }

        if ($referral->status === 'used') {
            return $this->error('REFERRAL_ALREADY_USED', 'This referral has already been used.', 409);
        }

        return $this->success([
            'digital_code' => $referral->digital_code,
            'status' => $referral->status,
            'patient' => [
                'name' => $referral->patient?->name,
                'nik' => $referral->patient?->nik,
                'dob' => $referral->patient?->dob?->toDateString(),
                'gender' => $referral->patient?->gender,
            ],
            'doctor' => [
                'name' => $referral->doctor?->name,
            ],
            'referring_clinic' => [
                'name' => $referral->clinic?->name,
            ],
            'destination_facility' => $referral->destination_facility,
            'destination_specialty' => $referral->destination_specialty,
            'urgency' => $referral->urgency,
            'reason' => $referral->reason,
            'summary' => $referral->summary,
            'expires_at' => $referral->expires_at?->toISOString(),
        ]);
    }
}
