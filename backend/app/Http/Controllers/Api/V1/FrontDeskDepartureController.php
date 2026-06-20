<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\FrontDesk\Services\CheckoutService;
use App\Domain\Reservation\Models\Reservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteCheckoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FrontDeskDepartureController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    /**
     * Get checkout preview with final bill calculation
     */
    public function preview(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeCheckoutAccess($reservation);

        try {
            $billData = $this->checkoutService->calculateFinalBill($reservation, [
                'actual_check_out_at' => $request->input('actual_check_out_at'),
                'damage_fee_amount' => $request->input('damage_fee_amount'),
                'damage_fee_notes' => $request->input('damage_fee_notes'),
                'late_checkout_hours' => $request->input('late_checkout_hours'),
                'lost_keycard_fee' => $request->input('lost_keycard_fee'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout preview loaded successfully.',
                'data' => $billData,
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate checkout preview.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Complete checkout process
     */
    public function completeCheckout(CompleteCheckoutRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            $this->authorizeCheckoutAccess($reservation);

            $result = $this->checkoutService->completeCheckout(
                $reservation,
                $request->validated(),
                $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil diselesaikan.',
                'data' => $result,
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete checkout.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get departures list (reservations checking out today or checked out today)
     */
    public function index(Request $request): JsonResponse
    {
        $propertyCode = $request->query('property_code', 'MAIN');
        $date = $request->query('date', now()->toDateString());

        $departures = Reservation::query()
            ->with(['primaryGuest', 'assignedRoom', 'roomType', 'property'])
            ->whereHas('property', fn ($q) => $q->where('code', strtoupper($propertyCode)))
            ->where(function ($q) use ($date) {
                $q->whereDate('check_out_date', '=', $date)
                    ->orWhere('reservation_status', 'checked_out');
            })
            ->whereIn('reservation_status', ['checked_in', 'checked_out'])
            ->orderBy('check_out_date')
            ->paginate($request->query('per_page', 20));

        $items = $departures
            ->getCollection()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'reservation_status' => $reservation->reservation_status,
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'actual_check_out' => $reservation->checked_out_at?->format('Y-m-d H:i:s'),
                'is_departing_today' => $reservation->check_out_date?->isToday(),
                'primary_guest' => [
                    'id' => $reservation->primaryGuest?->id,
                    'full_name' => $reservation->primaryGuest?->full_name,
                    'phone' => $reservation->primaryGuest?->phone,
                ],
                'assigned_room' => [
                    'id' => $reservation->assignedRoom?->id,
                    'room_number' => $reservation->assignedRoom?->room_number,
                    'current_status' => $reservation->assignedRoom?->current_status,
                    'housekeeping_status' => $reservation->assignedRoom?->housekeeping_status,
                ],
                'room_type' => [
                    'id' => $reservation->roomType?->id,
                    'name' => $reservation->roomType?->name,
                ],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Departures loaded successfully.',
            'data' => [
                'items' => $items,
            ],
            'meta' => [
                'total' => $departures->total(),
                'current_page' => $departures->currentPage(),
                'per_page' => $departures->perPage(),
                'last_page' => $departures->lastPage(),
                'from' => $departures->firstItem(),
                'to' => $departures->lastItem(),
            ],
        ]);
    }

    protected function authorizeCheckoutAccess(Reservation $reservation): void
    {
        if ($reservation->reservation_status !== 'checked_in') {
            throw new \InvalidArgumentException('Reservasi tidak sedang checked_in.');
        }
    }
}