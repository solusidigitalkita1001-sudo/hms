<?php

namespace App\Application\Portal\Actions;

use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\ReservationInquiry;
use App\Domain\Room\Models\RoomType;
use Illuminate\Validation\ValidationException;

class SubmitPortalInquiryAction
{
    public function handle(string $propertyCode, array $payload): ReservationInquiry
    {
        $property = Property::query()
            ->where('code', strtoupper($propertyCode))
            ->where('is_active', true)
            ->firstOrFail();

        $roomType = RoomType::query()
            ->where('property_id', $property->id)
            ->where('code', strtoupper($payload['room_type_code']))
            ->where('is_active', true)
            ->first();

        if (! $roomType) {
            throw ValidationException::withMessages([
                'room_type_code' => 'Tipe kamar yang dipilih tidak tersedia untuk properti ini.',
            ]);
        }

        return ReservationInquiry::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'full_name' => $payload['full_name'],
            'phone' => $payload['phone'],
            'email' => $payload['email'] ?? null,
            'check_in_date' => $payload['check_in_date'],
            'check_out_date' => $payload['check_out_date'],
            'guest_count' => $payload['guest_count'],
            'notes' => $payload['notes'] ?? null,
            'source' => 'portal',
            'status' => 'new',
        ]);
    }
}
