<?php

namespace Sentai\Shared\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Sentai\Shared\Application\DTO\IdempotentResponse;
use Sentai\Shared\Application\Exceptions\AuthenticationFailed;

/**
 * Cross-cutting HTTP concerns shared by contracted mutations: the mandatory durable
 * `Idempotency-Key` header, the request correlation identity and the idempotent
 * response projection.
 */
trait IdempotentMutationHttp
{
    protected function requireIdempotencyKey(Request $request): string
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));

        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > 255) {
            throw ValidationException::withMessages([
                'Idempotency-Key' => ['A non-empty Idempotency-Key header up to 255 characters is required.'],
            ]);
        }

        return $idempotencyKey;
    }

    protected function correlationId(Request $request): string
    {
        return (string) $request->attributes->get('correlation_id', 'unavailable');
    }

    protected function sourceSurface(Request $request): string
    {
        $surface = $request->attributes->get('sentai.source_surface');

        if (is_string($surface) && $surface !== '') {
            return $surface;
        }

        return $request->attributes->has('sentai.mobile_access') ? 'mobile' : 'backoffice';
    }

    protected function actorId(Request $request): string
    {
        $actorId = $request->attributes->get('sentai.actor_id');

        if (is_string($actorId) && $actorId !== '') {
            return $actorId;
        }

        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationFailed;
        }

        return (string) $user->getAuthIdentifier();
    }

    protected function idempotentResponse(IdempotentResponse $response): JsonResponse
    {
        return response()->json(
            $response->body,
            $response->status,
            $response->replayed ? ['Idempotency-Replayed' => 'true'] : [],
        );
    }
}
