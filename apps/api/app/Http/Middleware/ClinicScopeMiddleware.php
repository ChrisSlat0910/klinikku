<?php

namespace App\Http\Middleware;

use App\Models\QueueItem;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $clinicId = $this->resolveClinicId($request);

        if ($clinicId === null) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'CLINIC_CONTEXT_MISSING',
                    'message' => 'Clinic context could not be resolved from this request.',
                ],
                'meta' => null,
            ], 400);
        }

        app()->instance('clinic_id', $clinicId);

        return $next($request);
    }

    private function resolveClinicId(Request $request): ?int
    {
        // 1. From authenticated user (web staff / mobile staff)
        $user = $request->user();
        if ($user instanceof User) {
            return (int) $user->clinic_id;
        }

        // 2. From route parameter (public routes)
        $routeClinicId = $request->route('clinic_id');
        if (is_numeric($routeClinicId)) {
            return (int) $routeClinicId;
        }

        // 3. From queue token lookup (public queue tracking)
        $token = $request->route('token');
        if (is_string($token)) {
            $queueItem = QueueItem::withoutClinicScope()
                ->where('token', $token)
                ->first();
            if ($queueItem instanceof QueueItem) {
                return (int) $queueItem->clinic_id;
            }
        }

        return null;
    }
}
