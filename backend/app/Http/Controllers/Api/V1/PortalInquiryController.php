<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Portal\Actions\SubmitPortalInquiryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePortalInquiryRequest;
use Illuminate\Http\JsonResponse;

class PortalInquiryController extends Controller
{
    public function __construct(
        private readonly SubmitPortalInquiryAction $submitPortalInquiryAction,
    ) {}

    public function store(StorePortalInquiryRequest $request, string $propertyCode): JsonResponse
    {
        $inquiry = $this->submitPortalInquiryAction->handle($propertyCode, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inquiry berhasil dikirim. Tim kami akan segera menghubungi Anda.',
            'data' => [
                'id' => $inquiry->id,
                'status' => $inquiry->status,
                'full_name' => $inquiry->full_name,
                'room_type_code' => strtoupper($request->validated('room_type_code')),
                'check_in_date' => $inquiry->check_in_date?->format('Y-m-d'),
                'check_out_date' => $inquiry->check_out_date?->format('Y-m-d'),
            ],
            'meta' => [],
        ], 201);
    }
}
