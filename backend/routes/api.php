<?php

use App\Http\Controllers\Api\V1\AssetLoanController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessDateController;
use App\Http\Controllers\Api\V1\DashboardSummaryController;
use App\Http\Controllers\Api\V1\FrontDeskArrivalController;
use App\Http\Controllers\Api\V1\FrontDeskDepartureController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\GuestAuthController;
use App\Http\Controllers\Api\V1\GuestBookingController;
use App\Http\Controllers\Api\V1\GuestPortalController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InvoiceItemController;
use App\Http\Controllers\Api\V1\LoanableAssetController;
use App\Http\Controllers\Api\V1\NoShowController;
use App\Http\Controllers\Api\V1\PortalCmsController;
use App\Http\Controllers\Api\V1\PortalInquiryController;
use App\Http\Controllers\Api\V1\PublicBookingController;
use App\Http\Controllers\Api\V1\PublicRoomController;
use App\Http\Controllers\Api\V1\ReservationInquiryController;
use App\Http\Controllers\Api\V1\RoomConditionReportController;
use App\Http\Controllers\Api\V1\RoomMoveController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\StayExtendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API — auth.api-token middleware
|--------------------------------------------------------------------------
*/
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
        Route::get('/settings/business-date', [BusinessDateController::class, 'show']);
        Route::post('/settings/night-audit', [BusinessDateController::class, 'runNightAudit']);
        Route::get('/reservation-inquiries', [ReservationInquiryController::class, 'index']);
        Route::patch('/reservation-inquiries/{reservationInquiry}/status', [ReservationInquiryController::class, 'updateStatus']);
        Route::get('/front-desk/arrivals', [FrontDeskArrivalController::class, 'index']);
        Route::get('/front-desk/arrivals/{reservation}/assignable-rooms', [FrontDeskArrivalController::class, 'assignableRooms']);
        Route::patch('/front-desk/arrivals/{reservation}/assign-room', [FrontDeskArrivalController::class, 'assignRoom']);
        Route::patch('/front-desk/arrivals/{reservation}/verify-identity', [FrontDeskArrivalController::class, 'verifyIdentity']);
        Route::post('/front-desk/arrivals/{reservation}/complete-check-in', [FrontDeskArrivalController::class, 'completeCheckin']);
        Route::post('/front-desk/walk-in', [FrontDeskArrivalController::class, 'walkIn']);

        // Departures (Check-out)
        Route::get('/front-desk/departures', [FrontDeskDepartureController::class, 'index']);
        Route::get('/front-desk/departures/{reservation}/preview', [FrontDeskDepartureController::class, 'preview']);
        Route::post('/front-desk/departures/{reservation}/complete-checkout', [FrontDeskDepartureController::class, 'completeCheckout']);

        // Invoice Items
        Route::prefix('invoices/{invoice}')->group(function (): void {
            Route::get('/', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/items', [InvoiceItemController::class, 'index'])->name('invoices.items.index');
            Route::post('/items', [InvoiceItemController::class, 'store'])->name('invoices.items.store');
            Route::get('/items/{item}', [InvoiceItemController::class, 'show'])->name('invoices.items.show');
            Route::put('/items/{item}', [InvoiceItemController::class, 'update'])->name('invoices.items.update');
            Route::patch('/items/{item}', [InvoiceItemController::class, 'update'])->name('invoices.items.patch');
            Route::delete('/items/{item}', [InvoiceItemController::class, 'destroy'])->name('invoices.items.destroy');
            Route::post('/recalculate', [InvoiceController::class, 'recalculate'])->name('invoices.recalculate');
            Route::post('/void', [InvoiceController::class, 'void'])->name('invoices.void');
        });

        // Loanable Assets
        Route::apiResource('loanable-assets', LoanableAssetController::class);

        // Asset Loans (per reservation)
        Route::get('/reservations/{reservation}/asset-loans', [AssetLoanController::class, 'index']);
        Route::post('/reservations/{reservation}/asset-loans', [AssetLoanController::class, 'store']);
        Route::patch('/asset-loans/{assetLoan}/return', [AssetLoanController::class, 'return']);

        // Room Condition Reports
        Route::get('/reservations/{reservation}/condition-reports', [RoomConditionReportController::class, 'index']);
        Route::post('/reservations/{reservation}/condition-reports', [RoomConditionReportController::class, 'store']);
        Route::get('/condition-reports/{roomConditionReport}', [RoomConditionReportController::class, 'show']);
        Route::patch('/condition-reports/{roomConditionReport}/acknowledge', [RoomConditionReportController::class, 'acknowledge']);

        // Flow Exceptions: Extend Stay
        Route::post('/stays/{stayRecord}/extend', [StayExtendController::class, 'extend']);

        // Flow Exceptions: Room Move
        Route::post('/front-desk/reservations/{reservation}/move-room', [RoomMoveController::class, 'move']);

        // Flow Exceptions: No-Show
        Route::post('/front-desk/arrivals/{reservation}/mark-no-show', [NoShowController::class, 'markNoShow']);
    });

    /*
    |--------------------------------------------------------------------------
    | Public API — no authentication required
    |--------------------------------------------------------------------------
    */
    Route::get('/health', HealthCheckController::class);
    Route::get('/dashboard/summary', DashboardSummaryController::class);
    Route::get('/portal/{propertyCode}', [GuestPortalController::class, 'show']);
    Route::post('/portal/{propertyCode}/inquiries', [PortalInquiryController::class, 'store']);
    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings/ui', [SettingsController::class, 'updateUi']);

    // Public Room Search & Booking
    Route::get('/public/rooms/search', [PublicRoomController::class, 'search']);
    Route::get('/public/rooms/{propertyCode}/{roomTypeCode}', [PublicRoomController::class, 'show']);
    Route::post('/public/bookings', [PublicBookingController::class, 'store']);
    Route::get('/public/bookings/{bookingCode}', [PublicBookingController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Guest Portal API — token-based auth (guest-xxx)
    |--------------------------------------------------------------------------
    */
    Route::post('/guest/auth/login', [GuestAuthController::class, 'login']);
    Route::post('/guest/auth/logout', [GuestAuthController::class, 'logout']);
    Route::get('/guest/auth/me', [GuestAuthController::class, 'me']);
    Route::get('/guest/bookings', [GuestBookingController::class, 'index']);
    Route::get('/guest/bookings/{reservation}', [GuestBookingController::class, 'show']);

    // Guest Portal — Condition Reports
    Route::post('/guest/bookings/{reservation}/condition-reports', [GuestBookingController::class, 'storeConditionReport']);
    Route::get('/guest/bookings/{reservation}/condition-reports', [GuestBookingController::class, 'conditionReports']);

    // Guest Portal — Asset Loan Requests
    Route::post('/guest/bookings/{reservation}/asset-loans', [GuestBookingController::class, 'storeAssetLoan']);
    Route::get('/guest/bookings/{reservation}/asset-loans', [GuestBookingController::class, 'assetLoans']);

    // Guest Portal — Pre Check-in
    Route::post('/guest/bookings/{reservation}/pre-checkin', [GuestBookingController::class, 'storePreCheckin']);

    // Guest Portal — Service Requests (future use)
    Route::post('/guest/bookings/{reservation}/service-requests', [GuestBookingController::class, 'storeServiceRequest']);
});
