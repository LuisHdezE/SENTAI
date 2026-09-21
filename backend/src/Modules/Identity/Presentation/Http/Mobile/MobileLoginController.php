<?php

namespace Sentai\Modules\Identity\Presentation\Http\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentai\Modules\Identity\Application\Services\MobileAuthenticationService;

final readonly class MobileLoginController
{
    public function __construct(private MobileAuthenticationService $authentication)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:4096'],
        ]);

        $pair = $this->authentication->login(
            (string) $payload['email'],
            (string) $payload['password'],
            (string) $request->attributes->get('correlation_id', 'unavailable'),
        );

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $pair->accessToken,
            'access_expires_at' => $pair->accessExpiresAt->format(DATE_ATOM),
            'refresh_token' => $pair->refreshToken,
            'refresh_expires_at' => $pair->refreshExpiresAt->format(DATE_ATOM),
        ]);
    }
}
