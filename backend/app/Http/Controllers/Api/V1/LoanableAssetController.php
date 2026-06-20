<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\Models\LoanableAsset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLoanableAssetRequest;
use App\Http\Requests\Api\V1\UpdateLoanableAssetRequest;
use Illuminate\Http\JsonResponse;

class LoanableAssetController extends Controller
{
    public function index(): JsonResponse
    {
        $assets = LoanableAsset::query()
            ->with('property:id,code,name')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $assets,
        ]);
    }

    public function store(StoreLoanableAssetRequest $request): JsonResponse
    {
        $asset = LoanableAsset::query()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil ditambahkan.',
            'data' => $asset->fresh(),
        ], 201);
    }

    public function show(LoanableAsset $loanableAsset): JsonResponse
    {
        $loanableAsset->load(['property:id,code,name']);

        return response()->json([
            'success' => true,
            'data' => $loanableAsset,
        ]);
    }

    public function update(UpdateLoanableAssetRequest $request, LoanableAsset $loanableAsset): JsonResponse
    {
        $loanableAsset->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil diperbarui.',
            'data' => $loanableAsset->fresh(),
        ]);
    }

    public function destroy(LoanableAsset $loanableAsset): JsonResponse
    {
        $loanableAsset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil dihapus.',
        ]);
    }
}
