<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Settings\Actions\RunNightAuditAction;
use App\Application\Settings\Services\BusinessDateService;
use App\Domain\Property\Models\NightAudit;
use App\Domain\Property\Models\Property;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RunNightAuditRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessDateController extends Controller
{
    public function __construct(
        private readonly BusinessDateService $businessDateService,
        private readonly RunNightAuditAction $runNightAuditAction,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $property = $this->resolveProperty(
            $request->string('property_code')->value() ?: 'MAIN',
        );

        $latestAudit = NightAudit::query()
            ->where('property_id', $property->id)
            ->latest('business_date')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Business date loaded successfully.',
            'data' => [
                ...$this->businessDateService->snapshot($property),
                'latest_night_audit' => $latestAudit ? [
                    'business_date' => $latestAudit->business_date?->toDateString(),
                    'next_business_date' => $latestAudit->next_business_date?->toDateString(),
                    'status' => $latestAudit->status,
                    'completed_at' => $latestAudit->completed_at?->toIso8601String(),
                    'closed_by_user_id' => $latestAudit->closed_by_user_id,
                ] : null,
            ],
            'meta' => [],
        ]);
    }

    public function runNightAudit(RunNightAuditRequest $request): JsonResponse
    {
        $property = $this->resolveProperty(
            $request->string('property_code')->value() ?: 'MAIN',
        );

        $nightAudit = $this->runNightAuditAction->handle(
            $property,
            (int) $request->user()->id,
            $request->string('notes')->value() ?: null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Night audit completed successfully.',
            'data' => [
                'audit' => [
                    'id' => $nightAudit->id,
                    'business_date' => $nightAudit->business_date?->toDateString(),
                    'next_business_date' => $nightAudit->next_business_date?->toDateString(),
                    'status' => $nightAudit->status,
                    'completed_at' => $nightAudit->completed_at?->toIso8601String(),
                    'summary' => $nightAudit->summary_json ?? [],
                ],
                'business_date' => $this->businessDateService->snapshot($property),
            ],
            'meta' => [],
        ]);
    }

    private function resolveProperty(string $propertyCode): Property
    {
        return Property::query()
            ->where('code', strtoupper($propertyCode))
            ->firstOrFail();
    }
}
