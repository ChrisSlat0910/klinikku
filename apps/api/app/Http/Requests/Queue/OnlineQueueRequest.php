<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class OnlineQueueRequest extends FormRequest
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
            'chief_complaint' => ['required', 'string', 'max:500'],
        ];
    }
}
