<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Portal\Services\GuestPortalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GuestPortalController extends Controller
{
    public function __construct(
        private readonly GuestPortalService $guestPortalService,
    ) {}

    public function show(string $propertyCode): JsonResponse
    {
        $portal = $this->guestPortalService->handle($propertyCode);

        return response()->json([
            'success' => true,
            'message' => 'Guest portal loaded successfully.',
            'data' => $portal->toArray(),
            'meta' => [],
        ]);
    }
}
