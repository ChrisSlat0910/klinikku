<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    protected function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => null,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function paginated(mixed $data, array $meta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => $meta,
        ]);
    }

    protected function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'meta' => null,
        ], $status);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error('RESOURCE_NOT_FOUND', $message, 404);
    }

    protected function forbidden(string $message = 'Permission denied.'): JsonResponse
    {
        return $this->error('PERMISSION_DENIED', $message, 403);
    }
}
