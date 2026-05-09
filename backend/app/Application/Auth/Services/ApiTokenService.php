<?php

namespace App\Application\Auth\Services;

use App\Application\Auth\DataTransferObjects\AuthUserData;
use App\Domain\Auth\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Str;

class ApiTokenService
{
    public function issue(User $user, string $name = 'web'): AuthUserData
    {
        $plainTextToken = Str::random(64);

        $token = ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
        ]);

        return new AuthUserData(
            user: $user,
            accessToken: sprintf('%s|%s', $token->id, $plainTextToken),
        );
    }
}
