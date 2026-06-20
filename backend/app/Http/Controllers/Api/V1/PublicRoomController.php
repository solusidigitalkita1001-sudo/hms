<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Property\Models\Property;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicRoomController extends Controller
{
    /**
     * Public room search — find available rooms by date range & guest count.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'property_code' => ['required', 'string', 'exists:properties,code'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adult_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $property = Property::where('code', $request->input('property_code'))->firstOrFail();
        $adultCount = (int) $request->input('adult_count', 1);
        $childCount = (int) $request->input('child_count', 0);
        $totalGuests = $adultCount + $childCount;

        $roomTypes = RoomType::query()
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->where('capacity', '>=', $totalGuests)
            ->get()
            ->map(function (RoomType $roomType) use ($property, $request): array {
                $availableRooms = Room::query()
                    ->sellable()
                    ->where('room_type_id', $roomType->id)
                    ->where('property_id', $property->id)
                    ->count();

                return [
                    'code' => $roomType->code,
                    'name' => $roomType->name,
                    'description' => $roomType->description,
                    'capacity' => $roomType->capacity,
                    'base_price' => (float) $roomType->base_price,
                    'weekend_price' => (float) ($roomType->weekend_price ?? $roomType->base_price),
                    'available_rooms' => $availableRooms,
                    'is_available' => $availableRooms > 0,
                    'amenities' => $roomType->amenities ? explode(',', $roomType->amenities) : [],
                    'size_sqm' => $roomType->size_sqm,
                    'bed_type' => $roomType->bed_type,
                    'smoking_allowed' => (bool) ($roomType->smoking_allowed ?? false),
                ];
            })
            ->sortByDesc('is_available')
            ->sortBy('base_price')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'property' => [
                    'code' => $property->code,
                    'name' => $property->name,
                ],
                'search_params' => [
                    'check_in_date' => $request->input('check_in_date'),
                    'check_out_date' => $request->input('check_out_date'),
                    'adult_count' => $adultCount,
                    'child_count' => $childCount,
                ],
                'room_types' => $roomTypes,
                'total_available' => $roomTypes->sum('available_rooms'),
            ],
        ]);
    }

    /**
     * Public room detail — get a specific room type info.
     */
    public function show(string $propertyCode, string $roomTypeCode): JsonResponse
    {
        $property = Property::where('code', $propertyCode)->firstOrFail();

        $roomType = RoomType::query()
            ->where('property_id', $property->id)
            ->where('code', $roomTypeCode)
            ->where('is_active', true)
            ->firstOrFail();

        $availableCount = Room::query()
            ->sellable()
            ->where('room_type_id', $roomType->id)
            ->where('property_id', $property->id)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $roomType->code,
                'name' => $roomType->name,
                'description' => $roomType->description,
                'capacity' => $roomType->capacity,
                'base_price' => (float) $roomType->base_price,
                'weekend_price' => (float) ($roomType->weekend_price ?? $roomType->base_price),
                'available_rooms' => $availableCount,
                'amenities' => $roomType->amenities ? explode(',', $roomType->amenities) : [],
                'size_sqm' => $roomType->size_sqm,
                'bed_type' => $roomType->bed_type,
                'smoking_allowed' => (bool) ($roomType->smoking_allowed ?? false),
                'property' => [
                    'code' => $property->code,
                    'name' => $property->name,
                    'address' => $property->address,
                    'phone' => $property->phone,
                    'email' => $property->email,
                ],
            ],
        ]);
    }
}
