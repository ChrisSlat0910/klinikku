<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'exists:users,id'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
