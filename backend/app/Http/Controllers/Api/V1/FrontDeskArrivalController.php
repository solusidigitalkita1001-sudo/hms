<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\FrontDesk\Actions\AssignRoomToArrivalAction;
use App\Application\FrontDesk\Actions\CompleteCheckinAction;
use App\Application\FrontDesk\Actions\VerifyArrivalIdentityAction;
use App\Application\FrontDesk\Services\ArrivalQueueService;
use App\Application\FrontDesk\Services\AssignableRoomService;
use App\Domain\FrontDesk\Services\WalkInService;
use App\Domain\Reservation\Models\Reservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignRoomToArrivalRequest;
use App\Http\Requests\Api\V1\CompleteCheckinRequest;
use App\Http\Requests\Api\V1\VerifyArrivalIdentityRequest;
use App\Http\Requests\Api\V1\WalkInReservationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class FrontDeskArrivalController extends Controller
{
    public function __construct(
        private readonly ArrivalQueueService $arrivalQueueService,
        private readonly AssignableRoomService $assignableRoomService,
        private readonly AssignRoomToArrivalAction $assignRoomToArrivalAction,
        private readonly VerifyArrivalIdentityAction $verifyArrivalIdentityAction,
        private readonly CompleteCheckinAction $completeCheckinAction,
        private readonly WalkInService $walkInService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->arrivalQueueService->paginate($request->query());

        $items = $paginator
            ->getCollection()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'source' => $reservation->source,
                'reservation_status' => $reservation->reservation_status,
                'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'adult_count' => $reservation->adult_count,
                'child_count' => $reservation->child_count,
                'payment_status' => $reservation->payment_status,
                'guarantee_status' => $reservation->guarantee_status,
                'property' => [
                    'id' => $reservation->property?->id,
                    'code' => $reservation->property?->code,
                    'name' => $reservation->property?->name,
                ],
                'primary_guest' => [
                    'id' => $reservation->primaryGuest?->id,
                    'full_name' => $reservation->primaryGuest?->full_name,
                    'phone' => $reservation->primaryGuest?->phone,
                    'email' => $reservation->primaryGuest?->email,
                    'identity_verified' => $reservation->primaryGuest?->identity_verified,
                    'identity_verification_status' => $reservation->primaryGuest?->identity_verification_status,
                ],
                'room_type' => [
                    'id' => $reservation->roomType?->id,
                    'code' => $reservation->roomType?->code,
                    'name' => $reservation->roomType?->name,
                ],
                'assigned_room' => [
                    'id' => $reservation->assignedRoom?->id,
                    'room_number' => $reservation->assignedRoom?->room_number,
                    'current_status' => $reservation->assignedRoom?->current_status,
                ],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Arrival queue loaded successfully.',
            'data' => [
                'items' => $items,
                'filters' => [
                    'search' => (string) $request->query('search', ''),
                    'status' => (string) $request->query('status', ''),
                    'property_id' => $request->query('property_id'),
                    'date_from' => $request->query('date_from'),
                    'date_to' => $request->query('date_to'),
                    'source' => (string) $request->query('source', ''),
                    'sort_by' => (string) $request->query('sort_by', 'check_in_date'),
                    'sort_direction' => (string) $request->query('sort_direction', 'asc'),
                ],
            ],
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function assignableRooms(Reservation $reservation): JsonResponse
    {
        $rooms = $this->assignableRoomService->listForReservation($reservation)
            ->map(fn ($room): array => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'current_status' => $room->current_status,
                'room_type' => [
                    'id' => $room->roomType?->id,
                    'code' => $room->roomType?->code,
                    'name' => $room->roomType?->name,
                ],
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignable rooms loaded successfully.',
            'data' => [
                'items' => $rooms,
            ],
            'meta' => [
                'total' => $rooms->count(),
            ],
        ]);
    }

    public function assignRoom(AssignRoomToArrivalRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation = $this->assignRoomToArrivalAction->handle(
            $reservation,
            $request->validated(),
            (int) $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Room assignment berhasil diperbarui.',
            'data' => [
                'reservation_id' => $reservation->id,
                'reservation_status' => $reservation->reservation_status,
                'assigned_room' => [
                    'id' => $reservation->assignedRoom?->id,
                    'room_number' => $reservation->assignedRoom?->room_number,
                ],
            ],
            'meta' => [],
        ]);
    }

    public function verifyIdentity(VerifyArrivalIdentityRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation = $this->verifyArrivalIdentityAction->handle(
            $reservation,
            $request->validated(),
            (int) $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Identitas tamu utama berhasil diverifikasi.',
            'data' => [
                'reservation_id' => $reservation->id,
                'reservation_status' => $reservation->reservation_status,
                'primary_guest' => [
                    'id' => $reservation->primaryGuest?->id,
                    'full_name' => $reservation->primaryGuest?->full_name,
                    'id_type' => $reservation->primaryGuest?->id_type,
                    'id_number' => $reservation->primaryGuest?->id_number,
                    'identity_verified' => $reservation->primaryGuest?->identity_verified,
                ],
            ],
            'meta' => [],
        ]);
    }

    public function completeCheckin(CompleteCheckinRequest $request, Reservation $reservation): JsonResponse
    {
        $stayRecord = $this->completeCheckinAction->handle(
            $reservation,
            $request->validated(),
            (int) $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil diselesaikan.',
            'data' => [
                'reservation_id' => $stayRecord->reservation_id,
                'stay_record_id' => $stayRecord->id,
                'status' => $stayRecord->stay_status,
                'room_number' => $stayRecord->room?->room_number,
                'checked_in_at' => $stayRecord->actual_check_in_at?->toISOString(),
            ],
            'meta' => [],
        ]);
    }

    /**
     * Create walk-in reservation
     */
    public function walkIn(WalkInReservationRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $payload['property_id'] = $request->query('property_id');

            $result = $this->walkInService->createWalkIn(
                $payload,
                $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Walk-in reservation berhasil dibuat.',
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
                'message' => 'Failed to create walk-in reservation.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}