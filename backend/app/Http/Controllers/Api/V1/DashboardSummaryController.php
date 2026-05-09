<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Dashboard\Services\DashboardSummaryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardSummaryController extends Controller
{
    public function __construct(
        private readonly DashboardSummaryService $dashboardSummaryService,
    ) {}

    public function __invoke(): JsonResponse
    {
        $summary = $this->dashboardSummaryService->handle();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary loaded successfully.',
            'data' => $summary->toArray(),
            'meta' => [],
        ]);
    }
}
