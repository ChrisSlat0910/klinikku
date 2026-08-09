<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'task' => ['required', 'in:reminder-draft,prefill,daily-report'],
            'context' => ['nullable', 'array'],
        ];
    }
}
