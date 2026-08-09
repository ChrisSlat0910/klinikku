<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PatientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'nik' => ['required', 'string', 'size:16'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:M,F'],
            'clinic_id' => ['required', 'exists:clinics,id'],
        ];
    }
}
