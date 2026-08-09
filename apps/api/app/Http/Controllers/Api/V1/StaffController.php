<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Staff\CreateStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = User::where('clinic_id', $clinicId)->with('roles');

        if ($request->has('role')) {
            $query->role($request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $staff = $query->orderBy('name')->paginate(20);

        return $this->paginated($staff->items(), [
            'page' => $staff->currentPage(),
            'per_page' => $staff->perPage(),
            'total' => $staff->total(),
            'last_page' => $staff->lastPage(),
        ]);
    }

    public function store(CreateStaffRequest $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $validated = $request->validated();

        $user = DB::transaction(function () use ($clinicId, $validated): User {
            $user = User::create([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'nik' => $validated['nik'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'position' => $validated['position'] ?? null,
                'status' => 'active',
            ]);

            $user->syncRoles($validated['roles']);

            return $user;
        });

        return $this->success($user->load('roles'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $user = User::where('clinic_id', $clinicId)
            ->with(['roles', 'roles.permissions'])
            ->find($id);

        if (! $user instanceof User) {
            return $this->notFound('Staff not found.');
        }

        return $this->success($user);
    }

    public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $user = User::where('clinic_id', $clinicId)->find($id);

        if (! $user instanceof User) {
            return $this->notFound('Staff not found.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $user->update(array_filter([
                'name' => $validated['name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'position' => $validated['position'] ?? null,
                'status' => $validated['status'] ?? null,
            ]));

            if (isset($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }
        });

        return $this->success($user->fresh(['roles']));
    }

    public function destroy(int $id): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $currentUser = request()->user();

        $user = User::where('clinic_id', $clinicId)->find($id);

        if (! $user instanceof User) {
            return $this->notFound('Staff not found.');
        }

        if ($currentUser instanceof User && $currentUser->id === $user->id) {
            return $this->error('CANNOT_DELETE_SELF', 'You cannot delete your own account.', 403);
        }

        $user->update(['status' => 'inactive']);

        return $this->success(['message' => 'Staff deactivated successfully.']);
    }
}
