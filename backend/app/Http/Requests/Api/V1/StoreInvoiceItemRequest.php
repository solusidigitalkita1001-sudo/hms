<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Billing\Enums\InvoiceItemType;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_type' => ['required', 'string', 'in:'.implode(',', InvoiceItemType::values())],
            'item_code' => ['nullable', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'item_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        $validTypes = implode(', ', InvoiceItemType::values());

        return [
            'item_type.in' => "Item type harus salah dari: {$validTypes}.",
            'item_name.required' => 'Item name wajib diisi.',
            'unit_price.required' => 'Unit price wajib diisi.',
            'unit_price.min' => 'Unit price tidak boleh negatif.',
            'quantity.required' => 'Quantity wajib diisi.',
            'quantity.min' => 'Quantity harus lebih dari 0.',
        ];
    }
}