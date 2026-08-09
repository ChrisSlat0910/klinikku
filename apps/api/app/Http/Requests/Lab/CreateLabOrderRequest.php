<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

class CreateLabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'medical_record_id' => ['required', 'exists:medical_records,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'tests_requested' => ['required', 'array', 'min:1'],
            'tests_requested.*' => ['required', 'string'],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
