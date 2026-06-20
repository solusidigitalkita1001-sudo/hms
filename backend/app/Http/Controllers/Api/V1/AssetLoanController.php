<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\Models\AssetLoan;
use App\Domain\Inventory\Models\LoanableAsset;
use App\Domain\Reservation\Models\Reservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReturnAssetLoanRequest;
use App\Http\Requests\Api\V1\StoreAssetLoanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssetLoanController extends Controller
{
    /**
     * List asset loans for a reservation.
     */
    public function index(Reservation $reservation): JsonResponse
    {
        $loans = $reservation->assetLoans()
            ->with(['asset:id,name', 'staff:id,name'])
            ->orderBy('loaned_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $loans,
        ]);
    }

    /**
     * Loan an asset to a reservation.
     */
    public function store(StoreAssetLoanRequest $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validated();
        $asset = LoanableAsset::findOrFail($validated['asset_id']);

        if (! $asset->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Aset tidak tersedia untuk dipinjam.',
            ], 422);
        }

        $loan = DB::transaction(function () use ($reservation, $asset, $validated): AssetLoan {
            $asset->decrementStock();

            return AssetLoan::query()->create([
                'reservation_id' => $reservation->id,
                'asset_id' => $asset->id,
                'staff_id' => $validated['staff_id'] ?? auth()->id(),
                'loaned_at' => $validated['loaned_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil dipinjamkan.',
            'data' => $loan->fresh(['asset:id,name', 'staff:id,name']),
        ], 201);
    }

    /**
     * Return a loaned asset.
     */
    public function return(ReturnAssetLoanRequest $request, AssetLoan $assetLoan): JsonResponse
    {
        if ($assetLoan->isReturned()) {
            return response()->json([
                'success' => false,
                'message' => 'Aset ini sudah dikembalikan.',
            ], 422);
        }

        DB::transaction(function () use ($assetLoan, $request): void {
            $assetLoan->update([
                'returned_at' => now(),
                'return_condition' => $request->input('return_condition'),
                'charge_amount' => $request->input('charge_amount'),
                'notes' => $request->input('notes', $assetLoan->notes),
            ]);

            $assetLoan->asset->incrementStock();
        });

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil dikembalikan.',
            'data' => $assetLoan->fresh(['asset:id,name', 'staff:id,name']),
        ]);
    }
}
