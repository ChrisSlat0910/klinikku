<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Ai\AiRequest;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;

class AiController extends BaseController
{
    public function __construct(private AiService $aiService) {}

    public function generate(AiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->aiService->generate(
                $validated['task'],
                $validated['context'] ?? []
            );

            return $this->success($result);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'AI_UNAVAILABLE') {
                return $this->error('AI_UNAVAILABLE', 'AI service is temporarily unavailable. Please try again later.', 503);
            }

            throw $e;
        }
    }
}
