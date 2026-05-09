<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_login_returns_access_token_and_user_data(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin@local.test',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'admin@local.test')
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'username', 'avatar_url'],
                ],
            ]);
    }

    public function test_authenticated_user_endpoint_works_with_bearer_token(): void
    {
        $this->seed();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'admin');
    }
}
