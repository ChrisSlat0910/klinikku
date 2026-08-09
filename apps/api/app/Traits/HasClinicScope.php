<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasClinicScope
{
    public static function bootHasClinicScope(): void
    {
        static::addGlobalScope('clinic', function (Builder $builder): void {
            $clinicId = app('clinic_id');
            if ($clinicId !== null) {

                $builder->where(
                    static::newModelInstance()->getTable().'.clinic_id',
                    $clinicId
                );
            }
        });
    }

    /**
     * @return Builder<static>
     */
    public static function withoutClinicScope(): Builder
    {
        return static::withoutGlobalScope('clinic');
    }
}
