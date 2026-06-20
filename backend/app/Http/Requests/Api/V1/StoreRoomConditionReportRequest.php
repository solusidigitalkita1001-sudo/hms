<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomConditionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.photo_url' => ['nullable', 'string', 'max:2048'],
            'items.*.category' => ['required', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'reporter_type' => ['required', 'in:staff,guest'],
            'guest_name' => ['required_if:reporter_type,guest', 'nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
