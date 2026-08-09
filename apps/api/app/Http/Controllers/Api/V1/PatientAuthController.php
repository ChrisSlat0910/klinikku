<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\PatientLoginRequest;
use App\Http\Requests\Auth\PatientRegisterRequest;
use App\Models\Patient;
use App\Models\PatientAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PatientAuthController extends BaseController
{
    public function register(PatientRegisterRequest $request): JsonResponse
    {
        $existingPatient = Patient::withoutGlobalScope('clinic')
            ->where('clinic_id', $request->clinic_id)
            ->where('nik', $request->nik)
            ->first();

        if ($existingPatient !== null) {
            if ($existingPatient->account !== null) {
                return $this->error('ACCOUNT_EXISTS', 'An account with this NIK already exists.', 409);
            }

            $account = PatientAccount::create([
                'patient_id' => $existingPatient->id,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $account->createToken('mobile')->plainTextToken;

            return $this->success([
                'token' => $token,
                'patient' => $this->formatPatient($existingPatient),
                'history_migrated' => true,
            ], 201);
        }

        $patient = Patient::create([
            'clinic_id' => $request->clinic_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'nik' => $request->nik,
            'dob' => $request->dob,
            'gender' => $request->gender,
        ]);

        $account = PatientAccount::create([
            'patient_id' => $patient->id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $account->createToken('mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'patient' => $this->formatPatient($patient),
            'history_migrated' => false,
        ], 201);
    }

    public function login(PatientLoginRequest $request): JsonResponse
    {
        $account = PatientAccount::whereHas('patient', function ($q) use ($request): void {
            $q->withoutGlobalScope('clinic')->where('nik', $request->nik);
        })->with('patient')->first();

        if ($account === null || ! Hash::check($request->password, $account->password ?? '')) {
            return $this->error('INVALID_CREDENTIALS', 'NIK or password is incorrect.', 401);
        }

        $patient = $account->patient;
        if (! $patient instanceof Patient) {
            return $this->error('PATIENT_NOT_FOUND', 'Patient record not found.', 404);
        }

        $token = $account->createToken('mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'patient' => $this->formatPatient($patient),
        ]);
    }

    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $googleId = hash('sha256', $request->id_token);

        $account = PatientAccount::where('google_id', $googleId)->with('patient')->first();

        if ($account === null) {
            return $this->error('GOOGLE_ACCOUNT_NOT_LINKED', 'No account linked to this Google account. Please register first.', 404);
        }

        $patient = $account->patient;
        if (! $patient instanceof Patient) {
            return $this->error('PATIENT_NOT_FOUND', 'Patient record not found.', 404);
        }

        $token = $account->createToken('mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'patient' => $this->formatPatient($patient),
        ]);
    }

    /** @return array<string, mixed> */
    private function formatPatient(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'name' => $patient->name,
            'phone' => $patient->phone,
            'nik' => $patient->nik,
            'dob' => $patient->dob?->toDateString(),
            'gender' => $patient->gender,
            'clinic_id' => $patient->clinic_id,
        ];
    }
}
