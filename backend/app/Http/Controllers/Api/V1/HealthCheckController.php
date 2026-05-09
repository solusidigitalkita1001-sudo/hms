<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy.',
            'data' => [
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
            ],
            'meta' => [],
        ]);
    }
}
