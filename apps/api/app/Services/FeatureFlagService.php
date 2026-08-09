<?php

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    public function check(int $clinicId, string $feature): bool
    {
        $flags = $this->getFlags($clinicId);

        return (bool) ($flags[$feature] ?? false);
    }

    /** @return array<string, bool> */
    public function getFlags(int $clinicId): array
    {
        $cacheKey = "ff:{$clinicId}";

        /** @var array<string, bool> */
        return Cache::remember($cacheKey, 3600, function () use ($clinicId): array {
            $clinic = Clinic::find($clinicId);

            return $clinic !== null ? ($clinic->feature_flags ?? []) : [];
        });
    }

    public function invalidate(int $clinicId): void
    {
        Cache::forget("ff:{$clinicId}");
    }
}
