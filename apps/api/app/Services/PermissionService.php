<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function userCan(User $user, string $permission): bool
    {
        // Layer 3: Check per-user explicit REVOKE (wins over everything)
        $explicit = DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.model_id', $user->id)
            ->where('model_has_permissions.model_type', User::class)
            ->where('permissions.name', $permission)
            ->select('model_has_permissions.granted')
            ->first();

        if ($explicit !== null) {
            // REVOKE (granted = false) always wins
            if (! $explicit->granted) {
                return false;
            }

            // Explicit GRANT
            return true;
        }

        // Layer 2: Check role permissions
        return $user->hasPermissionTo($permission);
    }
}
