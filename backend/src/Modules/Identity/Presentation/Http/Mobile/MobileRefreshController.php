<?php

namespace Sentai\Modules\Identity\Presentation\Http\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentai\Modules\Identity\Application\Services\MobileAuthenticationService;

final readonly class MobileRefreshController
{
    public function __construct(private MobileAuthenticationService $authentication) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'refresh_token' => ['required', 'string', 'max:512'],
        ]);

        $pair = $this->authentication->refresh((string) $payload['refresh_token']);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $pair->accessToken,
            'access_expires_at' => $pair->accessExpiresAt->format(DATE_ATOM),
            'refresh_token' => $pair->refreshToken,
            'refresh_expires_at' => $pair->refreshExpiresAt->format(DATE_ATOM),
        ]);
    }
}
