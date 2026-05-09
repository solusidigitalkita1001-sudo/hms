<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardSummaryController;
use App\Http\Controllers\Api\V1\FrontDeskArrivalController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\GuestPortalController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\PortalCmsController;
use App\Http\Controllers\Api\V1\PortalInquiryController;
use App\Http\Controllers\Api\V1\ReservationInquiryController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
    Route::middleware('auth.api-token')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/profile/avatar', [AuthController::class, 'updateAvatar']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);
        Route::delete('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/settings/portal-cms', [PortalCmsController::class, 'show']);
        Route::put('/settings/portal-cms', [PortalCmsController::class, 'update']);
        Route::get('/reservation-inquiries', [ReservationInquiryController::class, 'index']);
        Route::patch('/reservation-inquiries/{reservationInquiry}/status', [ReservationInquiryController::class, 'updateStatus']);
        Route::get('/front-desk/arrivals', [FrontDeskArrivalController::class, 'index']);
        Route::get('/front-desk/arrivals/{reservation}/assignable-rooms', [FrontDeskArrivalController::class, 'assignableRooms']);
        Route::patch('/front-desk/arrivals/{reservation}/assign-room', [FrontDeskArrivalController::class, 'assignRoom']);
        Route::patch('/front-desk/arrivals/{reservation}/verify-identity', [FrontDeskArrivalController::class, 'verifyIdentity']);
        Route::post('/front-desk/arrivals/{reservation}/complete-check-in', [FrontDeskArrivalController::class, 'completeCheckin']);
    });
    Route::get('/health', HealthCheckController::class);
    Route::get('/dashboard/summary', DashboardSummaryController::class);
    Route::get('/portal/{propertyCode}', [GuestPortalController::class, 'show']);
    Route::post('/portal/{propertyCode}/inquiries', [PortalInquiryController::class, 'store']);
    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings/ui', [SettingsController::class, 'updateUi']);
});
