<?php

namespace App\Application\Auth\Services;

use App\Application\Auth\DataTransferObjects\AuthUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleLoginService
{
    public function __construct(
        private readonly ApiTokenService $apiTokenService,
    ) {}

    public function handle(array $googleUser): AuthUserData
    {
        return DB::transaction(function () use ($googleUser): AuthUserData {
            $user = User::query()
                ->where('google_id', $googleUser['id'])
                ->orWhere('email', $googleUser['email'])
                ->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => $googleUser['name'],
                    'username' => $this->generateUsername($googleUser['email']),
                    'email' => $googleUser['email'],
                    'google_id' => $googleUser['id'],
                    'avatar_url' => $googleUser['picture'] ?? null,
                    'password' => Hash::make(Str::random(40)),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'last_login_at' => now(),
                ]);
            } else {
                $user->forceFill([
                    'name' => $googleUser['name'],
                    'google_id' => $googleUser['id'],
                    'avatar_url' => $googleUser['picture'] ?? $user->avatar_url,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'last_login_at' => now(),
                ])->save();
            }

            return $this->apiTokenService->issue($user, 'google');
        });
    }

    private function generateUsername(string $email): string
    {
        $base = Str::of(Str::before($email, '@'))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        $candidate = $base !== '' ? $base : 'guest';
        $suffix = 0;

        while (User::query()->where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = sprintf('%s.%d', $base !== '' ? $base : 'guest', $suffix);
        }

        return $candidate;
    }
}
