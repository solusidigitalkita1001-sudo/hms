<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reservation\Models\ReservationInquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateReservationInquiryStatusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationInquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 10), 5), 100);

        $allowedSorts = [
            'id',
            'full_name',
            'phone',
            'guest_count',
            'check_in_date',
            'check_out_date',
            'status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query = ReservationInquiry::query()
            ->with(['property:id,code,name', 'roomType:id,code,name']);

        if ($status && in_array($status, ReservationInquiry::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        $inquiries = $paginator
            ->getCollection()
            ->map(fn (ReservationInquiry $inquiry): array => [
                'id' => $inquiry->id,
                'full_name' => $inquiry->full_name,
                'phone' => $inquiry->phone,
                'email' => $inquiry->email,
                'guest_count' => $inquiry->guest_count,
                'check_in_date' => $inquiry->check_in_date?->format('Y-m-d'),
                'check_out_date' => $inquiry->check_out_date?->format('Y-m-d'),
                'notes' => $inquiry->notes,
                'source' => $inquiry->source,
                'status' => $inquiry->status,
                'property' => [
                    'code' => $inquiry->property?->code,
                    'name' => $inquiry->property?->name,
                ],
                'room_type' => [
                    'code' => $inquiry->roomType?->code,
                    'name' => $inquiry->roomType?->name,
                ],
                'created_at' => $inquiry->created_at?->toISOString(),
            ])
            ->values();

        $summary = collect(ReservationInquiry::STATUSES)
            ->map(fn (string $itemStatus): array => [
                'status' => $itemStatus,
                'count' => ReservationInquiry::query()->where('status', $itemStatus)->count(),
            ])
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Booking inquiries loaded successfully.',
            'data' => [
                'summary' => $summary,
                'items' => $inquiries,
                'filters' => [
                    'status' => $status ?: 'all',
                    'search' => $search,
                ],
                'available_statuses' => ReservationInquiry::STATUSES,
                'sort' => [
                    'by' => $sortBy,
                    'direction' => $sortDirection,
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

    public function updateStatus(UpdateReservationInquiryStatusRequest $request, ReservationInquiry $reservationInquiry): JsonResponse
    {
        $reservationInquiry->update([
            'status' => $request->validated('status'),
        ]);

        $reservationInquiry->load(['property:id,code,name', 'roomType:id,code,name']);

        return response()->json([
            'success' => true,
            'message' => 'Status inquiry berhasil diperbarui.',
            'data' => [
                'id' => $reservationInquiry->id,
                'status' => $reservationInquiry->status,
                'full_name' => $reservationInquiry->full_name,
                'property' => [
                    'code' => $reservationInquiry->property?->code,
                    'name' => $reservationInquiry->property?->name,
                ],
                'room_type' => [
                    'code' => $reservationInquiry->roomType?->code,
                    'name' => $reservationInquiry->roomType?->name,
                ],
            ],
            'meta' => [],
        ]);
    }
}
