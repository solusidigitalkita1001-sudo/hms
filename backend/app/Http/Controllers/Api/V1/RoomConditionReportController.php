<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Models\RoomConditionReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcknowledgeRoomConditionReportRequest;
use App\Http\Requests\Api\V1\StoreRoomConditionReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoomConditionReportController extends Controller
{
    /**
     * List condition reports for a reservation.
     */
    public function index(Reservation $reservation): JsonResponse
    {
        $reports = $reservation->conditionReports()
            ->with(['room:id,room_number', 'reporter:id,name', 'acknowledgedBy:id,name'])
            ->orderBy('report_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Create a new condition report for a reservation.
     */
    public function store(StoreRoomConditionReportRequest $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validated();

        $report = DB::transaction(function () use ($reservation, $validated): RoomConditionReport {
            $windowExpiredAt = null;
            if ($validated['reporter_type'] === 'guest') {
                // Guest reports have a 30-minute window from check-in
                $windowExpiredAt = now()->addMinutes(30);
            }

            return RoomConditionReport::query()->create([
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->assigned_room_id,
                'reported_by' => auth()->id(),
                'reporter_type' => $validated['reporter_type'],
                'guest_name' => $validated['guest_name'] ?? null,
                'report_time' => now(),
                'window_expired_at' => $windowExpiredAt,
                'items' => $validated['items'],
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan kondisi kamar berhasil disimpan.',
            'data' => $report->fresh(['room:id,room_number', 'reporter:id,name']),
        ], 201);
    }

    /**
     * Acknowledge a condition report (staff marks it as reviewed).
     */
    public function acknowledge(
        AcknowledgeRoomConditionReportRequest $request,
        RoomConditionReport $roomConditionReport
    ): JsonResponse {
        if ($roomConditionReport->isAcknowledged()) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah di-acknowledge.',
            ], 422);
        }

        $roomConditionReport->update([
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
            'admin_notes' => $request->input('admin_notes', $roomConditionReport->admin_notes),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan kondisi kamar telah di-acknowledge.',
            'data' => $roomConditionReport->fresh([
                'room:id,room_number',
                'reporter:id,name',
                'acknowledgedBy:id,name',
            ]),
        ]);
    }

    /**
     * Show a single condition report.
     */
    public function show(RoomConditionReport $roomConditionReport): JsonResponse
    {
        $roomConditionReport->load([
            'room:id,room_number',
            'reporter:id,name',
            'acknowledgedBy:id,name',
            'reservation:id,booking_code,check_in_date,check_out_date',
        ]);

        return response()->json([
            'success' => true,
            'data' => $roomConditionReport,
        ]);
    }
}
