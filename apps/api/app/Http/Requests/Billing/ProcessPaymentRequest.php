<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:tunai,transfer,bpjs,kartu'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
