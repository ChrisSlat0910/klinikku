<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\QueueItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicQueueController extends BaseController
{
    public function track(string $token): JsonResponse
    {
        $cacheKey = "queue_status:{$token}";

        /** @var array{expired?: bool, status?: string, queue_number?: string, position?: int, doctor_name?: string, waiting_ahead?: int, estimated_wait_minutes?: int}|null */
        $data = Cache::remember($cacheKey, 10, function () use ($token): ?array {
            $queueItem = QueueItem::withoutGlobalScope('clinic')
                ->where('token', $token)
                ->with('doctor')
                ->first();

            if (! $queueItem instanceof QueueItem) {
                return null;
            }

            if (in_array($queueItem->status, ['done', 'skipped'])) {
                return ['expired' => true, 'status' => $queueItem->status];
            }

            $waitingAhead = QueueItem::withoutGlobalScope('clinic')
                ->where('clinic_id', $queueItem->clinic_id)
                ->where('doctor_id', $queueItem->doctor_id)
                ->where('status', 'waiting')
                ->where('position', '<', $queueItem->position)
                ->count();

            $doctor = $queueItem->doctor;
            $doctorName = $doctor instanceof User ? $doctor->name : 'Dokter';

            return [
                'queue_number' => $queueItem->queue_number,
                'position' => $queueItem->position,
                'status' => $queueItem->status,
                'doctor_name' => $doctorName,
                'waiting_ahead' => $waitingAhead,
                'estimated_wait_minutes' => $waitingAhead * 10,
                'expired' => false,
            ];
        });

        if ($data === null) {
            return $this->error('TOKEN_NOT_FOUND', 'Queue token not found.', 404);
        }

        if (! empty($data['expired'])) {
            return $this->error('QUEUE_EXPIRED', 'This queue has been completed or skipped.', 410);
        }

        return $this->success($data);
    }
}
