<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile_information(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Admin Baru',
                'username' => 'admin-baru',
                'email' => 'admin-baru@local.test',
                'avatar_url' => 'https://images.example.com/avatar-admin.png',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Admin Baru')
            ->assertJsonPath('data.username', 'admin-baru')
            ->assertJsonPath('data.email', 'admin-baru@local.test');

        $this->assertDatabaseHas('users', [
            'id' => 1,
            'name' => 'Admin Baru',
            'username' => 'admin-baru',
            'email' => 'admin-baru@local.test',
        ]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password berhasil diperbarui.');

        $user = User::query()->findOrFail(1);

        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_authenticated_user_can_upload_profile_avatar(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/auth/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.png', 200, 200),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $avatarUrl = $response->json('data.avatar_url');

        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('/uploads/profile-avatars/', $avatarUrl);

        $user = User::query()->findOrFail(1);

        $this->assertSame($avatarUrl, $user->avatar_url);

        $avatarPath = public_path(ltrim((string) parse_url($avatarUrl, PHP_URL_PATH), '/'));

        $this->assertFileExists($avatarPath);

        File::delete($avatarPath);
    }

    public function test_password_change_requires_valid_current_password(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'salah-total',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    private function loginToken(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ])->json('data.access_token');
    }
}
