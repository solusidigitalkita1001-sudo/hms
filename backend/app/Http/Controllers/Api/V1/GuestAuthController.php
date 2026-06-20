<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Guest\Models\Guest;
use App\Domain\Reservation\Models\Reservation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestAuthController extends Controller
{
    /**
     * Guest login via booking code + phone.
     * Returns a guest API token for subsequent requests.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $reservation = Reservation::query()
            ->where('booking_code', $validated['booking_code'])
            ->whereHas('primaryGuest', fn ($q) => $q->where('phone', $validated['phone']))
            ->with('primaryGuest')
            ->first();

        if (! $reservation || ! $reservation->primaryGuest) {
            return response()->json([
                'success' => false,
                'message' => 'Kode booking atau nomor HP tidak sesuai.',
            ], 401);
        }

        $guest = $reservation->primaryGuest;

        // Generate a simple token for the guest session
        $token = 'guest-' . Str::random(48);

        // Store token in cache or a simple guest_tokens table
        // For now, we'll use a simple approach — cache the token
        cache()->put("guest_token_{$token}", [
            'guest_id' => $guest->id,
            'reservation_id' => $reservation->id,
            'booking_code' => $reservation->booking_code,
        ], now()->addDays(3));

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'guest' => [
                    'id' => $guest->id,
                    'full_name' => $guest->full_name,
                    'phone' => $guest->phone,
                    'email' => $guest->email,
                ],
                'booking' => [
                    'code' => $reservation->booking_code,
                    'status' => $reservation->reservation_status,
                    'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                    'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                    'property_name' => $reservation->property?->name,
                ],
            ],
        ]);
    }

    /**
     * Get guest profile from token.
     */
    public function me(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token || ! Str::startsWith($token, 'guest-')) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid.',
            ], 401);
        }

        $session = cache()->get("guest_token_{$token}");

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi telah berakhir. Silakan login kembali.',
            ], 401);
        }

        $guest = Guest::find($session['guest_id']);

        if (! $guest) {
            return response()->json([
                'success' => false,
                'message' => 'Tamu tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'phone' => $guest->phone,
                'email' => $guest->email,
                'total_stays' => $guest->total_stays,
                'last_stay_at' => $guest->last_stay_at?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Guest logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token && Str::startsWith($token, 'guest-')) {
            cache()->forget("guest_token_{$token}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
