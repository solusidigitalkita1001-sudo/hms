<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CompleteCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_check_out_at' => ['nullable', 'date'],
            'damage_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'damage_fee_notes' => ['nullable', 'string'],
            'late_checkout_hours' => ['nullable', 'integer', 'min:0'],
            'late_checkout_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'late_checkout_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_method_code' => ['nullable', 'string'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string'],
            'room_inspected' => ['boolean'],
            'room_condition_notes' => ['nullable', 'string'],
            'keycard_returned' => ['boolean'],
            'lost_keycard_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'damage_fee_amount.min' => 'Damage fee tidak boleh negatif.',
            'late_checkout_hours.min' => 'Late checkout hours tidak boleh negatif.',
            'payment_amount.min' => 'Payment amount tidak boleh negatif.',
        ];
    }
}