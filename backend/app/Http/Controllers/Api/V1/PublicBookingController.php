<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\FrontDesk\Services\AvailabilityService;
use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Models\RoomType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {}

    /**
     * Create a public booking (no auth required).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_code' => ['required', 'string', 'exists:properties,code'],
            'room_type_code' => ['required', 'string', 'exists:room_types,code'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adult_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            // Booker info
            'booker_name' => ['required', 'string', 'max:255'],
            'booker_phone' => ['required', 'string', 'max:20'],
            'booker_email' => ['nullable', 'email', 'max:255'],
            'booker_nik' => ['nullable', 'string', 'max:50'],
            // Guest info (if different from booker)
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            // Booking for someone else
            'is_booking_for_other' => ['boolean'],
            // Preferences
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ]);

        $property = Property::where('code', $validated['property_code'])->firstOrFail();
        $roomType = RoomType::where('code', $validated['room_type_code'])
            ->where('property_id', $property->id)
            ->firstOrFail();

        $checkIn = \Carbon\Carbon::parse($validated['check_in_date']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out_date']);

        // Check availability
        $isAvailable = $this->availabilityService->isRoomTypeAvailable(
            $roomType->id,
            $property->id,
            $checkIn,
            $checkOut,
        );

        if (! $isAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon maaf, tipe kamar ini tidak tersedia untuk tanggal yang dipilih.',
            ], 422);
        }

        $booking = DB::transaction(function () use ($validated, $property, $roomType): array {
            // Create or find guest
            $guestData = [
                'property_id' => $property->id,
                'full_name' => $validated['guest_name'] ?? $validated['booker_name'],
                'phone' => $validated['guest_phone'] ?? $validated['booker_phone'],
                'email' => $validated['booker_email'] ?? null,
            ];

            if (! empty($validated['booker_nik'])) {
                $guestData['id_number'] = $validated['booker_nik'];
                $guestData['id_type'] = 'KTP';
            }

            $guest = Guest::query()->firstOrCreate(
                ['phone' => $guestData['phone'], 'property_id' => $property->id],
                $guestData,
            );

            // Generate booking code
            $bookingCode = 'BK-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

            // Create reservation
            $reservation = Reservation::query()->create([
                'property_id' => $property->id,
                'primary_guest_id' => $guest->id,
                'room_type_id' => $roomType->id,
                'booking_code' => $bookingCode,
                'source' => 'online_portal',
                'reservation_status' => 'confirmed',
                'adult_count' => (int) ($validated['adult_count'] ?? 1),
                'child_count' => (int) ($validated['child_count'] ?? 0),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'payment_status' => 'pending',
                'guarantee_status' => 'booking',
                'deposit_amount' => 0,
                'special_requests' => $validated['special_requests'] ?? null,
                'booked_at' => now(),
                'booker_name' => $validated['booker_name'],
                'booker_phone' => $validated['booker_phone'],
                'is_booking_for_other' => (bool) ($validated['is_booking_for_other'] ?? false),
                'guest_name_on_booking' => $validated['guest_name'] ?? null,
            ]);

            return [
                'booking_code' => $bookingCode,
                'guest_name' => $guest->full_name,
                'guest_phone' => $guest->phone,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil! Silakan catat kode booking Anda.',
            'data' => [
                'booking_code' => $booking['booking_code'],
                'guest_name' => $booking['guest_name'],
                'guest_phone' => $booking['guest_phone'],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'room_type' => $roomType->name,
                'property_name' => $property->name,
                'status' => 'confirmed',
            ],
        ], 201);
    }

    /**
     * Lookup a booking by code + phone (no auth).
     */
    public function show(Request $request, string $bookingCode): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $request->input('phone');

        $reservation = Reservation::query()
            ->where('booking_code', $bookingCode)
            ->whereHas('primaryGuest', fn ($q) => $q->where('phone', $phone))
            ->with([
                'primaryGuest:id,full_name,phone,email',
                'roomType:id,code,name,base_price',
                'assignedRoom:id,room_number',
                'property:id,code,name,address,phone,email',
                'invoices' => function ($q): void {
                    $q->select('id', 'reservation_id', 'invoice_status', 'grand_total', 'paid_amount', 'remaining_amount');
                },
            ])
            ->first();

        if (! $reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan. Periksa kembali kode booking dan nomor HP.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking_code' => $reservation->booking_code,
                'status' => $reservation->reservation_status,
                'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'adult_count' => $reservation->adult_count,
                'child_count' => $reservation->child_count,
                'guest' => [
                    'name' => $reservation->primaryGuest?->full_name,
                    'phone' => $reservation->primaryGuest?->phone,
                    'email' => $reservation->primaryGuest?->email,
                ],
                'room_type' => $reservation->roomType?->name,
                'assigned_room' => $reservation->assignedRoom?->room_number,
                'property' => [
                    'name' => $reservation->property?->name,
                    'address' => $reservation->property?->address,
                    'phone' => $reservation->property?->phone,
                ],
                'invoices' => $reservation->invoices->map(fn ($inv) => [
                    'status' => $inv->invoice_status,
                    'grand_total' => (float) $inv->grand_total,
                    'paid_amount' => (float) $inv->paid_amount,
                    'remaining' => (float) $inv->remaining_amount,
                ]),
                'special_requests' => $reservation->special_requests,
                'booked_at' => $reservation->booked_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
