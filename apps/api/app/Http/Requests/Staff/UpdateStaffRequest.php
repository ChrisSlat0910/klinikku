<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['required_with:roles', 'string', 'exists:roles,name'],
        ];
    }
}
