<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureFlagMiddleware
{
    public function __construct(private FeatureFlagService $featureFlagService) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $clinicId = app('clinic_id');

        if ($clinicId === null || ! $this->featureFlagService->check((int) $clinicId, $feature)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'FEATURE_NOT_AVAILABLE',
                    'message' => "Feature '{$feature}' is not available for this clinic type.",
                ],
                'meta' => null,
            ], 403);
        }

        return $next($request);
    }
}
