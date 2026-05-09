<?php

namespace App\Application\Auth\Services;

use App\Application\Auth\DataTransferObjects\AuthUserData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ManualLoginService
{
    public function __construct(
        private readonly ApiTokenService $apiTokenService,
    ) {}

    public function handle(array $credentials): AuthUserData
    {
        $user = User::query()
            ->where(function ($query) use ($credentials): void {
                $query
                    ->where('email', $credentials['identifier'])
                    ->orWhere('username', $credentials['identifier']);
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => ['Email/username atau password tidak valid.'],
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return $this->apiTokenService->issue($user);
    }
}
