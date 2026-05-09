<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyArrivalIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest.full_name' => ['required', 'string', 'max:120'],
            'guest.id_type' => ['required', 'string', 'max:40'],
            'guest.id_number' => ['required', 'string', 'max:120'],
            'guest.phone' => ['nullable', 'string', 'max:40'],
            'guest.email' => ['nullable', 'email:rfc', 'max:255'],
            'guest.address' => ['nullable', 'string', 'max:1000'],
            'guest.nationality' => ['nullable', 'string', 'max:12'],
            'guest.birth_date' => ['nullable', 'date'],
            'guest.gender' => ['nullable', 'string', 'max:20'],
            'guest.emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'guest.emergency_contact_phone' => ['nullable', 'string', 'max:40'],
        ];
    }
}
