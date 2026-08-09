<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

class SubmitLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'results' => ['required', 'array'],
            'interpretation' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
