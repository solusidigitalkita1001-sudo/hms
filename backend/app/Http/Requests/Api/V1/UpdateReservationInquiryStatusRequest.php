<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Reservation\Models\ReservationInquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationInquiryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(ReservationInquiry::STATUSES)],
        ];
    }
}
