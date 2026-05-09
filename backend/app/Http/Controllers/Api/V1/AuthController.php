<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Auth\Services\ManualLoginService;
use App\Domain\Auth\Models\ApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileAvatarRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly ManualLoginService $manualLoginService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $authUser = $this->manualLoginService->handle($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => $authUser->toArray(),
            'meta' => [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user loaded successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ],
            'meta' => [],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill($request->validated())->save();

        return response()->json([
            'success' => true,
            'message' => 'Informasi akun berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ],
            'meta' => [],
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        if (! Hash::check($payload['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak valid.',
            ]);
        }

        $user->forceFill([
            'password' => $payload['password'],
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
            'data' => null,
            'meta' => [],
        ]);
    }

    public function updateAvatar(UpdateProfileAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $avatar = $request->file('avatar');
        $directory = public_path('uploads/profile-avatars');

        File::ensureDirectoryExists($directory);

        if ($user->avatar_url) {
            $currentAvatarPath = public_path(ltrim((string) parse_url($user->avatar_url, PHP_URL_PATH), '/'));

            if (str_contains($currentAvatarPath, public_path('uploads/profile-avatars')) && File::exists($currentAvatarPath)) {
                File::delete($currentAvatarPath);
            }
        }

        $filename = Str::uuid()->toString().'.'.$avatar->getClientOriginalExtension();
        $avatar->move($directory, $filename);

        $avatarUrl = url('/uploads/profile-avatars/'.$filename);

        $user->forceFill([
            'avatar_url' => $avatarUrl,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ],
            'meta' => [],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var ApiToken|null $currentToken */
        $currentToken = $request->attributes->get('current_api_token');
        $currentToken?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
            'meta' => [],
        ]);
    }
}
