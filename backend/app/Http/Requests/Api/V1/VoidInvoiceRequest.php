<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VoidInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'approval_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'void_reason.required' => 'Void reason wajib diisi.',
            'void_reason.min' => 'Void reason minimal 10 karakter.',
            'void_reason.max' => 'Void reason maximal 1000 karakter.',
        ];
    }
}