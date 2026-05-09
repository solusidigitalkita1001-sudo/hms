<?php

namespace App\Application\Auth\DataTransferObjects;

use App\Models\User;

class AuthUserData
{
    public function __construct(
        public readonly User $user,
        public readonly string $accessToken,
    ) {}

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar_url,
            ],
        ];
    }
}
