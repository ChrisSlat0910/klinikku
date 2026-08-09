<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class RegisterQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'exists:users,id'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'patient_name' => ['required_without:patient_id', 'string', 'max:255'],
            'patient_phone' => ['required_without:patient_id', 'string', 'max:20'],
            'chief_complaint' => ['required', 'string', 'max:500'],
        ];
    }
}
