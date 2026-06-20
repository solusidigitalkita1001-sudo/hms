<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Guest\Models\Guest;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Models\RoomConditionReport;
use App\Domain\Inventory\Models\AssetLoan;
use App\Domain\Inventory\Models\LoanableAsset;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    /**
     * Get the authenticated guest from token.
     */
    protected function resolveGuest(Request $request): ?Guest
    {
        $token = $request->bearerToken();

        if (! $token || ! Str::startsWith($token, 'guest-')) {
            return null;
        }

        $session = cache()->get("guest_token_{$token}");

        if (! $session) {
            return null;
        }

        return Guest::find($session['guest_id']);
    }

    /**
     * List authenticated guest's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $guest = $this->resolveGuest($request);

        if (! $guest) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak terautentikasi.',
            ], 401);
        }

        $bookings = $guest->reservations()
            ->with([
                'roomType:id,code,name,base_price',
                'assignedRoom:id,room_number',
                'property:id,code,name',
                'invoices' => function ($q): void {
                    $q->select('id', 'reservation_id', 'invoice_status', 'grand_total', 'paid_amount', 'remaining_amount');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Reservation $r): array => [
                'id' => $r->id,
                'booking_code' => $r->booking_code,
                'status' => $r->reservation_status,
                'check_in_date' => $r->check_in_date?->format('Y-m-d'),
                'check_out_date' => $r->check_out_date?->format('Y-m-d'),
                'room_type' => $r->roomType?->name,
                'assigned_room' => $r->assignedRoom?->room_number,
                'property_name' => $r->property?->name,
                'adult_count' => $r->adult_count,
                'child_count' => $r->child_count,
                'invoice_status' => $r->invoices->first()?->invoice_status,
                'grand_total' => (float) ($r->invoices->first()?->grand_total ?? 0),
                'paid_amount' => (float) ($r->invoices->first()?->paid_amount ?? 0),
                'remaining' => (float) ($r->invoices->first()?->remaining_amount ?? 0),
                'booked_at' => $r->booked_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Show a specific booking for the authenticated guest.
     */
    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);

        if (! $guest) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak terautentikasi.',
            ], 401);
        }

        // Verify the booking belongs to this guest
        if ($reservation->primary_guest_id !== $guest->id) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $reservation->load([
            'primaryGuest:id,full_name,phone,email',
            'roomType:id,code,name,base_price,description,capacity,bed_type,size_sqm',
            'assignedRoom:id,room_number,floor',
            'property:id,code,name,address,phone,email',
            'invoices.items',
            'invoices.payments',
            'stayRecords',
        ]);

        $invoice = $reservation->invoices->first();

        return response()->json([
            'success' => true,
            'data' => [
                'booking_code' => $reservation->booking_code,
                'status' => $reservation->reservation_status,
                'source' => $reservation->source,
                'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'adult_count' => $reservation->adult_count,
                'child_count' => $reservation->child_count,
                'guest' => [
                    'name' => $reservation->primaryGuest?->full_name,
                    'phone' => $reservation->primaryGuest?->phone,
                    'email' => $reservation->primaryGuest?->email,
                ],
                'room_type' => $reservation->roomType ? [
                    'name' => $reservation->roomType->name,
                    'description' => $reservation->roomType->description,
                    'capacity' => $reservation->roomType->capacity,
                    'bed_type' => $reservation->roomType->bed_type,
                    'size_sqm' => $reservation->roomType->size_sqm,
                    'base_price' => (float) $reservation->roomType->base_price,
                ] : null,
                'assigned_room' => $reservation->assignedRoom ? [
                    'room_number' => $reservation->assignedRoom->room_number,
                    'floor' => $reservation->assignedRoom->floor,
                ] : null,
                'property' => $reservation->property ? [
                    'name' => $reservation->property->name,
                    'address' => $reservation->property->address,
                    'phone' => $reservation->property->phone,
                ] : null,
                'invoice' => $invoice ? [
                    'status' => $invoice->invoice_status,
                    'subtotal' => (float) $invoice->subtotal_amount,
                    'tax' => (float) $invoice->tax_amount,
                    'grand_total' => (float) $invoice->grand_total,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'remaining' => (float) $invoice->remaining_amount,
                    'items' => $invoice->items->map(fn ($item) => [
                        'name' => $item->item_name,
                        'type' => $item->item_type,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (int) $item->quantity,
                        'line_total' => (float) $item->line_total,
                    ]),
                    'payments' => $invoice->payments->map(fn ($payment) => [
                        'method' => $payment->payment_method_code,
                        'amount' => (float) $payment->amount,
                        'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    ]),
                ] : null,
                'stay_status' => $reservation->stayRecords->first()?->stay_status,
                'special_requests' => $reservation->special_requests,
                'booked_at' => $reservation->booked_at?->format('Y-m-d H:i:s'),
                'checked_in_at' => $reservation->checked_in_at?->format('Y-m-d H:i:s'),
                'checked_out_at' => $reservation->checked_out_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  Guest Condition Reports
    // ──────────────────────────────────────────────

    /**
     * List condition reports for the guest's booking.
     */
    public function conditionReports(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (! $guest || $reservation->primary_guest_id !== $guest->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $reports = $reservation->conditionReports()
            ->orderBy('report_time', 'desc')
            ->get()
            ->map(fn (RoomConditionReport $r) => [
                'id' => $r->id,
                'reporter_type' => $r->reporter_type,
                'guest_name' => $r->guest_name,
                'report_time' => $r->report_time?->format('Y-m-d H:i:s'),
                'window_expired_at' => $r->window_expired_at?->format('Y-m-d H:i:s'),
                'items' => $r->items,
                'is_acknowledged' => $r->isAcknowledged(),
                'acknowledged_at' => $r->acknowledged_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json(['success' => true, 'data' => $reports]);
    }

    /**
     * Submit a condition report as a guest.
     */
    public function storeConditionReport(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (! $guest || $reservation->primary_guest_id !== $guest->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.category' => ['required', 'string', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:500'],
        ]);

        $report = DB::transaction(function () use ($reservation, $validated, $guest): RoomConditionReport {
            return RoomConditionReport::query()->create([
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->assigned_room_id,
                'reported_by' => null, // guest, not admin
                'reporter_type' => 'guest',
                'guest_name' => $guest->full_name,
                'report_time' => now(),
                'window_expired_at' => now()->addMinutes(30),
                'items' => $validated['items'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan kondisi kamar berhasil dikirim.',
            'data' => [
                'id' => $report->id,
                'report_time' => $report->report_time?->format('Y-m-d H:i:s'),
                'window_expired_at' => $report->window_expired_at?->format('Y-m-d H:i:s'),
                'items' => $report->items,
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────
    //  Guest Asset Loan Requests
    // ──────────────────────────────────────────────

    /**
     * List asset loans for the guest's booking.
     */
    public function assetLoans(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (! $guest || $reservation->primary_guest_id !== $guest->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $loans = $reservation->assetLoans()
            ->with('asset:id,name')
            ->orderBy('loaned_at', 'desc')
            ->get()
            ->map(fn (AssetLoan $l) => [
                'id' => $l->id,
                'asset_name' => $l->asset?->name,
                'loaned_at' => $l->loaned_at?->format('Y-m-d H:i:s'),
                'returned_at' => $l->returned_at?->format('Y-m-d H:i:s'),
                'return_condition' => $l->return_condition,
                'status' => $l->returned_at ? 'returned' : 'active',
            ]);

        return response()->json(['success' => true, 'data' => $loans]);
    }

    /**
     * Request an asset loan as a guest.
     */
    public function storeAssetLoan(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (! $guest || $reservation->primary_guest_id !== $guest->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'asset_id' => ['required', 'exists:loanable_assets,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $asset = LoanableAsset::findOrFail($validated['asset_id']);
        $qty = $validated['quantity'] ?? 1;

        if ($asset->available_stock < $qty) {
            return response()->json([
                'success' => false,
                'message' => "Stok '{$asset->name}' tidak mencukupi (tersedia: {$asset->available_stock}).",
            ], 422);
        }

        $loan = DB::transaction(function () use ($reservation, $validated, $asset, $qty): AssetLoan {
            $asset->decrement('available_stock', $qty);

            return AssetLoan::query()->create([
                'reservation_id' => $reservation->id,
                'asset_id' => $validated['asset_id'],
                'staff_id' => null,
                'loaned_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Permintaan peminjaman '{$asset->name}' berhasil.",
            'data' => [
                'id' => $loan->id,
                'asset_name' => $asset->name,
                'quantity' => $qty,
                'loaned_at' => $loan->loaned_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────
    //  Guest Pre Check-in
    // ──────────────────────────────────────────────

    /**
     * Submit pre check-in data before arrival.
     */
    public function storePreCheckin(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (! $guest || $reservation->primary_guest_id !== $guest->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        if (! in_array($reservation->reservation_status, ['confirmed', 'checked_in'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Pre check-in hanya bisa dilakukan untuk booking dengan status confirmed atau checked_in.',
            ], 422);
        }

        $validated = $request->validate([
            'estimated_arrival_at' => ['nullable', 'date', 'after:now'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        // Update guest identity if provided
        if (! empty($validated['id_type']) && ! empty($validated['id_number'])) {
            $guest->forceFill([
                'id_type' => $validated['id_type'],
                'id_number' => $validated['id_number'],
            ])->save();
        }

        // Update reservation with pre-checkin data
        $reservation->forceFill([
            'special_requests' => $validated['special_requests']
                ? trim(($reservation->special_requests ?? '') . "\n[Pre Check-in]: " . $validated['special_requests'])
                : $reservation->special_requests,
            'adult_count' => $validated['guest_count'] ?? $reservation->adult_count,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Data pre check-in berhasil disimpan.',
            'data' => [
                'booking_code' => $reservation->booking_code,
                'pre_checkin_at' => now()->toIso8601String(),
                'estimated_arrival_at' => $validated['estimated_arrival_at'] ?? null,
                'special_requests' => $validated['special_requests'] ?? null,
                'guest' => [
                    'id_type' => $guest->id_type,
                    'id_number' => $guest->id_number ? substr($guest->id_number, 0, 6) . '****' : null,
                ],
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    //  Guest Service Requests (placeholder)
    // ──────────────────────────────────────────────

    /**
     * Submit a service request (e.g. HK, room service).
     */
    public function storeServiceRequest(Request $request, Reservation $reservation): JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (! $guest || $reservation->primary_guest_id !== $guest->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'service_type' => ['required', 'string', 'in:hk,room_service,maintenance,other'],
            'description' => ['required', 'string', 'max:500'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
        ]);

        // For now, log the request. Future: create a dedicated service_request model.
        // For MVP, we'll store it as a note in the reservation.
        $note = "[Guest Service Request - {$validated['service_type']}]: {$validated['description']}";
        if (! empty($validated['priority'])) {
            $note .= " (Priority: {$validated['priority']})";
        }

        $reservation->forceFill([
            'internal_notes' => trim(($reservation->internal_notes ?? '')."\n\n".$note),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Permintaan layanan telah dikirim.',
            'data' => [
                'service_type' => $validated['service_type'],
                'description' => $validated['description'],
                'submitted_at' => now()->toIso8601String(),
            ],
        ], 201);
    }
}
