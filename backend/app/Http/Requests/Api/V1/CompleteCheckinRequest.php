<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CompleteCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirm_identity_verified' => ['required', 'boolean'],
            'confirm_registration_signed' => ['nullable', 'boolean'],
            'confirm_terms_accepted' => ['required', 'boolean'],
            'issue_keycard' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
