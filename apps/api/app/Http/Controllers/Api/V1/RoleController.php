<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return $this->success($roles);
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::all(['id', 'name']);

        return $this->success($permissions);
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $existing = Role::where('name', $validated['name'])->where('guard_name', 'web')->first();

        if ($existing instanceof Role) {
            return $this->error('ROLE_EXISTS', 'A role with this name already exists.', 409);
        }

        $role = DB::transaction(function () use ($validated): Role {
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
            $role->syncPermissions($validated['permissions']);

            return $role;
        });

        return $this->success($role->load('permissions'), 201);
    }

    public function update(CreateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role instanceof Role) {
            return $this->notFound('Role not found.');
        }

        $role->syncPermissions($request->validated()['permissions']);

        return $this->success($role->fresh('permissions'));
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role instanceof Role) {
            return $this->notFound('Role not found.');
        }

        $systemRoles = ['owner', 'dokter', 'dokter_pj', 'perawat', 'apoteker', 'kasir', 'analis', 'admin'];
        if (in_array($role->name, $systemRoles)) {
            return $this->error('CANNOT_DELETE_SYSTEM_ROLE', 'System roles cannot be deleted.', 403);
        }

        $role->delete();

        return $this->success(['message' => 'Role deleted successfully.']);
    }

    public function assign(AssignRoleRequest $request, int $userId): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $user = User::where('clinic_id', $clinicId)->find($userId);

        if (! $user instanceof User) {
            return $this->notFound('Staff not found.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $user->syncRoles($validated['roles']);

            if (isset($validated['permissions'])) {
                DB::table('model_has_permissions')
                    ->where('model_id', $user->id)
                    ->where('model_type', User::class)
                    ->delete();

                foreach ($validated['permissions'] as $permData) {
                    $permission = Permission::where('name', $permData['name'])
                        ->where('guard_name', 'web')
                        ->first();

                    if ($permission instanceof Permission) {
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $permission->id,
                            'model_type' => User::class,
                            'model_id' => $user->id,
                            'granted' => $permData['granted'],
                        ]);
                    }
                }
            }
        });

        $freshUser = User::where('clinic_id', $clinicId)->find($userId);

        return $this->success([
            'user_id' => $userId,
            'roles' => $freshUser instanceof User ? $freshUser->getRoleNames() : [],
            'message' => 'Roles and permissions updated successfully.',
        ]);
    }
}
