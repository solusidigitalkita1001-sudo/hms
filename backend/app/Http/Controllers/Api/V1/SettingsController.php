<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Settings\Actions\UpdateUiSettingsAction;
use App\Application\Settings\Services\SettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUiSettingsRequest;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly UpdateUiSettingsAction $updateUiSettingsAction,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Settings loaded successfully.',
            'data' => $this->settingsService->grouped(),
            'meta' => [],
        ]);
    }

    public function updateUi(UpdateUiSettingsRequest $request): JsonResponse
    {
        $settings = $this->updateUiSettingsAction->handle($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'UI settings updated successfully.',
            'data' => $settings,
            'meta' => [],
        ]);
    }
}
