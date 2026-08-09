<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\QueueItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DisplayController extends BaseController
{
    public function show(int $clinicId, int $doctorId): JsonResponse
    {
        $cacheKey = "display:{$clinicId}:{$doctorId}";

        /** @var array<string, mixed> */
        $data = Cache::remember($cacheKey, 10, function () use ($clinicId, $doctorId): array {
            $called = QueueItem::withoutGlobalScope('clinic')
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('status', 'called')
                ->whereDate('queue_date', now()->toDateString())
                ->orderByDesc('called_at')
                ->first(['queue_number', 'patient_name', 'called_at']);

            $waiting = QueueItem::withoutGlobalScope('clinic')
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctorId)
                ->where('status', 'waiting')
                ->whereDate('queue_date', now()->toDateString())
                ->orderBy('position')
                ->take(5)
                ->get(['queue_number', 'patient_name', 'position']);

            $doctor = User::find($doctorId);

            return [
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'doctor_name' => $doctor instanceof User ? $doctor->name : 'Dokter',
                'now_serving' => $called ? [
                    'queue_number' => $called->queue_number,
                    'patient_name' => $called->patient_name,
                ] : null,
                'next_queue' => $waiting->map(fn (QueueItem $item) => [
                    'queue_number' => $item->queue_number,
                    'patient_name' => $item->patient_name,
                    'position' => $item->position,
                ]),
                'updated_at' => now()->toISOString(),
            ];
        });

        return $this->success($data);
    }
}
