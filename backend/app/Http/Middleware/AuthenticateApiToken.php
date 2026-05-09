<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken || ! str_contains($bearerToken, '|')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'data' => null,
                'meta' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        [$tokenId, $plainTextToken] = explode('|', $bearerToken, 2);

        $token = ApiToken::query()
            ->whereKey($tokenId)
            ->first();

        if (
            ! $token
            || ! hash_equals($token->token, hash('sha256', $plainTextToken))
            || ($token->expires_at && $token->expires_at->isPast())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'data' => null,
                'meta' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('current_api_token', $token);

        return $next($request);
    }
}
