<?php

namespace App\Http\Requests\Rme;

use Illuminate\Foundation\Http\FormRequest;

class CreateRmeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'queue_item_id' => ['required', 'exists:queue_items,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'subjective' => ['required', 'string', 'max:2000'],
            'objective' => ['required', 'string', 'max:2000'],
            'assessment' => ['required', 'string', 'max:2000'],
            'plan' => ['required', 'string', 'max:2000'],
            'tindakan' => ['nullable', 'array'],
            'tindakan.*.name' => ['required_with:tindakan', 'string'],
            'tindakan.*.code' => ['nullable', 'string'],
            'diagnoses' => ['required', 'array', 'min:1'],
            'diagnoses.*.diagnosis_id' => ['required', 'exists:diagnoses,id'],
            'diagnoses.*.type' => ['required', 'in:primary,secondary'],
        ];
    }
}
