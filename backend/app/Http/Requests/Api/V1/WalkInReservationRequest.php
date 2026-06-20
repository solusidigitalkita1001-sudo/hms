<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class WalkInReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Guest info
            'guest_id' => ['nullable', 'exists:guests,id'],
            'guest_full_name' => ['required_without:guest_id', 'string', 'max:255'],
            'guest_phone' => ['required_without:guest_id', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_id_type' => ['nullable', 'string', 'max:40'],
            'guest_id_number' => ['nullable', 'string', 'max:120'],

            // Room info
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adult_count' => ['required', 'integer', 'min:1'],
            'child_count' => ['nullable', 'integer', 'min:0'],

            // Pricing
            'rate_per_night' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],

            // Payment (optional, can collect later)
            'payment_method_code' => ['nullable', 'string', 'max:50'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],

            // Additional
            'special_requests' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'in:walk_in,phone,website,ota,other'],

            // Options
            'auto_check_in' => ['boolean'],
            'create_invoice' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_full_name.required_without' => 'Guest name wajib diisi jika guest_id tidak dipilih.',
            'guest_phone.required_without' => 'Guest phone wajib diisi jika guest_id tidak dipilih.',
            'room_type_id.required' => 'Room type wajib dipilih.',
            'check_in_date.required' => 'Check-in date wajib diisi.',
            'check_in_date.after_or_equal' => 'Check-in date tidak boleh di masa lalu.',
            'check_out_date.required' => 'Check-out date wajib diisi.',
            'check_out_date.after' => 'Check-out date harus setelah check-in date.',
            'adult_count.required' => 'Adult count wajib diisi.',
            'adult_count.min' => 'Adult count minimal 1.',
        ];
    }

    protected function prepareForValidation()
    {
        // Set default values
        $this->merge([
            'source' => $this->input('source', 'walk_in'),
            'auto_check_in' => $this->input('auto_check_in', false),
            'create_invoice' => $this->input('create_invoice', true),
            'adult_count' => $this->input('adult_count', 1),
            'child_count' => $this->input('child_count', 0),
        ]);
    }
}