<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Rme\SearchIcd10Request;
use App\Services\RmeService;
use Illuminate\Http\JsonResponse;

class Icd10Controller extends BaseController
{
    public function __construct(private RmeService $rmeService) {}

    public function search(SearchIcd10Request $request): JsonResponse
    {
        $results = $this->rmeService->searchIcd10(
            $request->validated()['q'],
            (int) ($request->validated()['limit'] ?? 10)
        );

        return $this->success($results);
    }
}
