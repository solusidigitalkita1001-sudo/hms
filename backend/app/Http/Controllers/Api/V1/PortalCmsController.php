<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Portal\Services\PortalCmsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePortalCmsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalCmsController extends Controller
{
    public function __construct(
        private readonly PortalCmsService $portalCmsService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $content = $this->portalCmsService->getContent(
            $request->string('property_code')->value() ?: 'MAIN',
        );

        return response()->json([
            'success' => true,
            'message' => 'Portal CMS loaded successfully.',
            'data' => $content,
            'meta' => [],
        ]);
    }

    public function update(UpdatePortalCmsRequest $request): JsonResponse
    {
        $content = $this->portalCmsService->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Portal CMS updated successfully.',
            'data' => $content,
            'meta' => [],
        ]);
    }
}
