<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Auth\Services\GoogleLoginService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleLoginService $googleLoginService,
    ) {}

    public function redirect(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');
        $frontendUrl = rtrim(config('services.frontend.url'), '/');

        if (! $clientId || ! $redirectUri) {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Google OAuth belum dikonfigurasi.'));
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'include_granted_scopes' => 'true',
        ]);

        return redirect()->away(sprintf('https://accounts.google.com/o/oauth2/v2/auth?%s', $query));
    }

    public function callback(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');
        $frontendUrl = rtrim(config('services.frontend.url'), '/');

        if (! $clientId || ! $clientSecret || ! $redirectUri || ! $frontendUrl) {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Google OAuth belum dikonfigurasi.'));
        }

        $code = request()->string('code')->value();

        if ($code === '') {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Google authorization code tidak ditemukan.'));
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if ($tokenResponse->failed()) {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Gagal menukar authorization code Google.'));
        }

        $googleToken = $tokenResponse->json('access_token');

        if (! is_string($googleToken) || $googleToken === '') {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Access token Google tidak tersedia.'));
        }

        $googleUserResponse = Http::withToken($googleToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if ($googleUserResponse->failed()) {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Gagal mengambil profil Google.'));
        }

        $googleUser = $googleUserResponse->json();

        if (! is_array($googleUser) || ! isset($googleUser['sub'], $googleUser['email'], $googleUser['name'])) {
            return redirect()->away($frontendUrl.'/login?error='.urlencode('Profil Google tidak lengkap.'));
        }

        $authUser = $this->googleLoginService->handle([
            'id' => $googleUser['sub'],
            'email' => $googleUser['email'],
            'name' => $googleUser['name'],
            'picture' => $googleUser['picture'] ?? null,
        ]);

        $query = http_build_query([
            'token' => $authUser->accessToken,
            'name' => $authUser->user->name,
            'email' => $authUser->user->email,
            'avatar_url' => $authUser->user->avatar_url,
            'username' => $authUser->user->username,
            'nonce' => Str::uuid()->toString(),
        ]);

        return redirect()->away($frontendUrl.'/auth/callback?'.$query);
    }
}
