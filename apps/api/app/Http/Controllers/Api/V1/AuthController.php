<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\StaffLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function login(StaffLoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            return $this->error('INVALID_CREDENTIALS', 'Email or password is incorrect.', 401);
        }

        if ($user->status !== 'active') {
            return $this->error('ACCOUNT_INACTIVE', 'This account has been deactivated.', 403);
        }

        $token = $user->createToken('staff')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            // @phpstan-ignore-next-line
            $user->currentAccessToken()?->delete();
        }

        return $this->success(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        return $this->success($this->formatUser($user));
    }

    /** @return array<string, mixed> */
    private function formatUser(User $user): array
    {
        $user->load('clinic');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'clinic_id' => $user->clinic_id,
            'clinic' => $user->clinic ? [
                'id' => $user->clinic->id,
                'name' => $user->clinic->name,
                'slug' => $user->clinic->slug,
                'clinic_type' => $user->clinic->clinic_type,
            ] : null,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'status' => $user->status,
        ];
    }
}
