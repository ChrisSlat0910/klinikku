<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $dbOk = true;
        $redisOk = true;

        try {
            DB::connection()->getPdo();
        } catch (\Exception) {
            $dbOk = false;
        }

        try {
            Redis::ping();
        } catch (\Exception) {
            $redisOk = false;
        }

        return $this->success([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => $dbOk ? 'ok' : 'error',
                'cache' => $redisOk ? 'ok' : 'error',
            ],
        ]);
    }
}
