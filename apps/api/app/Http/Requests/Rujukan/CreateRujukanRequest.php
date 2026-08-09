<?php

namespace App\Http\Requests\Rujukan;

use Illuminate\Foundation\Http\FormRequest;

class CreateRujukanRequest extends FormRequest
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
            'destination_facility' => ['required', 'string', 'max:255'],
            'destination_specialty' => ['required', 'string', 'max:255'],
            'urgency' => ['required', 'in:routine,urgent,emergency'],
            'reason' => ['required', 'string', 'max:2000'],
            'summary' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
