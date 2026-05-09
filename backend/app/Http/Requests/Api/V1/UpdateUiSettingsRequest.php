<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'layout_mode' => ['required', 'string', Rule::in(['sidebar', 'navbar'])],
            'sidebar_collapsed' => ['required', 'boolean'],
            'table_density' => ['required', 'string', Rule::in(['compact', 'comfortable'])],
        ];
    }
}
